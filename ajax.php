<?php

require_once('functions.php');

// security controll
session_start();
if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' || !isset($_SERVER['HTTP_REFERER']) || substr($_SERVER['HTTP_REFERER'], 0, strlen(HOME_URL)) != HOME_URL || $_POST['sec_tok'] != $_SESSION['sec_tok'])
	exit;

// set $_GET from parameters from URL
$url_get_params = explode('?', $_SERVER['HTTP_REFERER']);
if (count($url_get_params) > 1) {
	$url_get_params = explode('&', $url_get_params[1]);
	foreach ($url_get_params as $val) {
		$one_param = explode('=', $val);
		$_GET[$one_param[0]] = $one_param[1];
	}
}

if (isset($_POST['call']))
	call_user_func($_POST['call']);

// creates new konzultation
function kon_create_new () {
	$cols = [];
	$vals = [];
	$errs = [];
	$cook = [];
	$current_user = get_logged_user();
	if (!$current_user || $current_user['level'] < KANTOR_LEVEL)
		return;
	touch_user_action($current_user['login']);

	// check date
	$_POST['date'] = str_replace(' ', '', $_POST['date']);
	$d = DateTime::createFromFormat(DATE_CZ, $_POST['date']);
	if ($d && $d->format(DATE_CZ) === $_POST['date']) {
		array_push($cols, 'execution_date');
		array_push($vals, '"' . $d->format(DATE_DB) . '"');
	} else {
		array_push($errs, $GLOBALS['lang']->errors->badDate);
	}

	// check start time
	$t = DateTime::createFromFormat(TIME_S, $_POST['start']);
	if ($t && $t->format(TIME_S) === $_POST['start']) {
		array_push($cols, 'start_time');
		array_push($vals, '"' . $t->format(TIME_DB) . '"');
	} else {
		array_push($errs, $GLOBALS['lang']->errors->badKonStart);
	}

	// section duration
	$sc = explode(':', $_POST['sec_dur']);
	if (!empty($_POST['sec_dur']) && is_numeric($sc[0]) && floor($sc[0]) == $sc[0] && is_numeric($sc[1]) && strlen($sc[1]) == 2 && $sc[1] < 60) {
		array_push($cols, 'section_duration');
		array_push($vals, '"' . $_POST['sec_dur'] . '"');
	} else {
		array_push($errs, $GLOBALS['lang']->errors->badKonDur);
	}

	// sections number
	if (is_numeric($_POST['sec_num']) && $_POST['sec_num'] > 0) {
		array_push($cols, 'section_amount');
		array_push($vals, $_POST['sec_num']);
	} else {
		array_push($errs, $GLOBALS['lang']->errors->badKonNum);
	}

	// check consultation limit to 24 hours
	if (in_array('section_duration', $cols) && in_array('section_amount', $cols)) {
		if (($sc[0] * 60 + $sc[1]) * $_POST['sec_num'] >= 60 * 24)
			array_push($errs, $GLOBALS['lang']->errors->konAllDay);
	}

	// append note if set
	if (!empty($_POST['note'])) {
		array_push($cols, 'kantor_note');
		array_push($vals, '"' . addslashes($_POST['note']) . '"');
	}

	// check past
	$now = time();
	if ($d && $t && (($d->format(DATE_DB) < date(DATE_DB, $now)) || ($d->format(DATE_DB) == date(DATE_DB, $now) && $t->format(TIME_DB) <= date(TIME_DB, $now)))) {
		array_push($errs, $GLOBALS['lang']->errors->konPast);
	}

	if (empty($errs)) {
		// check db for collisions
		$kon_from = to_timestamp($t->format(TIME_DB_FULL), $d->format(DATE_DB));
		$kon_to = $kon_from + (to_timestamp($_POST['sec_dur']) * $_POST['sec_num']);
		$result = kon_db('SELECT * FROM kon_consultation WHERE author_id="' . $current_user['login'] . '"');
		while (($row = $result->fetch_assoc()) != null) {
			// if deleting current (while duplicating), skip it
			if (isset($_POST['del_orig']) && $row['id'] == $_POST['del_orig'])
				continue;
			$r_from = to_timestamp($row['start_time'], $row['execution_date']);
			$r_to = $r_from + (to_timestamp($row['section_duration']) * $row['section_amount']);
			// check if two intervals overlap
			if ($kon_from < $r_to && $r_from < $kon_to)
				array_push($errs, repl_str($GLOBALS['lang']->errors->konOverlap, date(DATE_CZ . ' ' . TIME_S, timezone_adjustment($r_from)), date(DATE_CZ . ' ' . TIME_S, timezone_adjustment($r_to))));
		}
		if (!empty($errs)) {
			echo 'Error' . implode('|', $errs);
			return;
		}

		// add author
		array_push($cols, 'author_id');
		array_push($vals, '"' . $current_user['login'] . '"');

		// add stud_filters
		if (!empty($_POST['stud_filter'])) {
			array_push($cols, 'stud_filter');
			array_push($vals, '"' . $_POST['stud_filter'] . '"');
		}

		// add room
		if (!empty($_POST['room'])) {
			array_push($cols, 'room');
			array_push($vals, '"' . addslashes($_POST['room']) . '"');
		}

		// add initial history mark
		array_push($cols, 'history');
		if (!empty($_POST['note']))
			array_push($vals, '"' . date(DATE_WITH_TIME) . ' - ' . $current_user['login'] . ' ' . history_encode('|lang,ajax,initHistoryWith|' . $d->format(DATE_CZ) . '|' . $t->format(TIME_S) . '|' . $_POST['sec_num'] . '|' . history_encode(addslashes($_POST['note']))) . '|"');
		else
			array_push($vals, '"' . date(DATE_WITH_TIME) . ' - ' . $current_user['login'] . ' ' . history_encode('|lang,ajax,initHistoryWithout|' . $d->format(DATE_CZ) . '|' . $t->format(TIME_S) . '|' . $_POST['sec_num']) . '|"');

		kon_db('INSERT INTO kon_consultation (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')');
		$last_id = kon_db('SELECT LAST_INSERT_ID()')->fetch_assoc()['LAST_INSERT_ID()'];

		// add notifications
		if (!empty($_POST['checkboxes'])) {
			$sql = 'UPDATE kon_notifications SET ';
			foreach (explode(',', $_POST['checkboxes']) as $value) {
				$sql .= $value . '="' . $current_user['login'] . '",';
			}
			$sql = rtrim($sql, ',') . ' WHERE id=' . $last_id;
			kon_db($sql);
		}

		// delete original if that option was selected
		if (isset($_POST['del_orig']) && date(DATE_DB) < kon_db('SELECT execution_date FROM kon_consultation WHERE id=' . $_POST['del_orig'])->fetch_assoc()['execution_date'])
			kon_db('DELETE FROM kon_consultation WHERE id=' . $_POST['del_orig']);

		// save last values used to cookies
		foreach (['start', 'sec_dur', 'sec_num'] as $value) {
			setcookie('val_' . $value, $_POST[$value], time() + REMEMBERME_DURATION);
		}
		
		require 'pre_show_consultations.php';
	} else {
		echo 'Error' . implode('|', $errs);
	}	
}

// sends back e-mail adresses of users signed to provided consultation
function get_message_receivers () {
	$result = kon_db('SELECT email FROM kon_user NATURAL JOIN kon_signed WHERE id=' . $_POST['target']);
	$emails = [];
	foreach ($result->fetch_all() as $val)
		if (!in_array($val[0], $emails))
			array_push($emails, $val[0]);

	$toWrite = '';
	for ($i=0; $i < count($emails); $i++) { 
		$toWrite .= '&lt;' . $emails[$i] . '&gt;';
		if ($i+1 < count($emails))
			$toWrite .= ', ';
	}
	echo $toWrite;
}

// deletes existing consultation and inform signed users if message is provided
function kon_delete () {
	// check if past
	if (kon_editable_section($_POST['target']) != 1)
		return;

	$current_user = get_logged_user();
	touch_user_action($current_user['login']);

	if (!empty($_POST['message'])) {
		// find out, who to send message to and send it
		$result = kon_db('SELECT DISTINCT email FROM kon_user NATURAL JOIN kon_signed WHERE id=' . $_POST['target']);
		$emails = '';
		while ($res = $result->fetch_assoc()) {
			$emails .= $res['email'] . ',';
		}
		send_message($_POST['message'], substr($emails, 0, -2), $GLOBALS['lang']->messages->konDeleteWithMsg, $_POST['target']);
	}

	prepare_notification('kon_delete', $_POST['target'], $current_user);
	kon_db('DELETE FROM kon_consultation WHERE id=' . $_POST['target']); // DB consistency ensured by "on delete cascade"
	require 'pre_show_consultations.php';

}

// deletes or disables single consultation row and/or sign off users from selected sections
function kon_disable_row () {
	$available = kon_editable_section($_POST['target']);
	if ($available < 0)
		return;
	$sectionsA = explode(',', $_POST['sections']);
	$consult_res = kon_db('SELECT * FROM kon_consultation WHERE id=' . $_POST['target'])->fetch_assoc();
	$sec_array = [];
	$start_time = $consult_res['start_time'];
	$section_duration = to_timestamp($consult_res['section_duration']);
	// for ($i=0; $i < $consult_res['section_amount']; $i++) {
	// 	if ($i+1 >= $available)
	// 		$sec_array[$i] = date(TIME_DB, timezone_adjustment(to_timestamp($start_time) + $i * $section_duration));
	// }
	foreach ($sectionsA as $key => $value) {
		$sectionsA[$key] = $value . ':00';
		if (strlen($sectionsA[$key]) < 8)
			$sectionsA[$key] = '0' . $sectionsA[$key];
	}

	$current_user = get_logged_user();
	touch_user_action($current_user['login']);

	// if user chose to enable sections
	if ($_POST['disable'] < 0) {
		if (empty($consult_res['disabled_sections']))
			$disabled_from_db = array();
		else
			$disabled_from_db = explode(',', $consult_res['disabled_sections']);
		$added_sections = [];
		foreach ($sectionsA as $value) {
			if (($key = array_search($value, $disabled_from_db)) !== false) {
				array_push($added_sections, substr($disabled_from_db[$key], 0, -3));
				unset($disabled_from_db[$key]);
			}
		}
		kon_db('UPDATE kon_consultation SET disabled_sections="' . implode(',', $disabled_from_db) . '" WHERE id=' . $_POST['target']);
		update_kon_history($_POST['target'], '|lang,ajax,konSecEnable|' . implode(', ', $added_sections));
		prepare_notification('kon_section_add', $_POST['target'], $current_user, [ 'sections' => $added_sections ]);

	// handle disable
	} else {
		// handle signed users
		$result = kon_db('SELECT * FROM kon_signed WHERE id=' . $_POST['target']);
		$kicked_users = [];
		$kicked_for_history = [];
		while (($signed = $result->fetch_assoc()) != null) {
			if (in_array($signed['section'], $sectionsA)) {
				$new_user = kon_db('SELECT * FROM kon_user WHERE login="' . $signed['login'] . '"')->fetch_assoc();
				$new_user['section'] = $signed['section'];
				array_push($kicked_users, $new_user);
				array_push($kicked_for_history, $new_user['login']);
				kon_db('DELETE FROM kon_signed WHERE id=' . $_POST['target'] . ' AND section="' . $signed['section'] . '"');
			}
		}
		if (!empty($kicked_users)) {
			if (to_timestamp($consult_res['start_time'], $consult_res['execution_date']) - time() < LAST_MINUTE)
				prepare_notification('student_last_min_logout', $_POST['target'], $current_user, [ 'users' => $kicked_users ]);
			else
				prepare_notification('student_logout', $_POST['target'], $current_user, [ 'sections' => $kicked_users ]);
		}

		if ($_POST['disable'] == 1) {
			// disable
			if (empty($consult_res['disabled_sections']))
				$disabled_from_db = array();
			else
				$disabled_from_db = explode(',', $consult_res['disabled_sections']);
			$disabled_sections = [];
			foreach ($sectionsA as $value) {
				if (!in_array($value, $disabled_from_db)) {
					array_push($disabled_sections, substr($value, 0, -3));
					array_push($disabled_from_db, $value);
				}
			}
			// previous values are at the start of the function
			$section_amount = $consult_res['section_amount'];
			$last_section = date(TIME_DB_FULL, timezone_adjustment(to_timestamp($start_time) + ($section_amount - 1) * $section_duration));
			$disabled_another = array();
			sort($disabled_from_db);
			// move section from after midnight to the end
			while ($disabled_from_db[0] < $start_time) {
				array_push($disabled_from_db, array_splice($disabled_from_db, 0, 1)[0]);
			}
			// adjust start if needed
			for ($i=0; $i < count($disabled_from_db); $i++) {
				if ($disabled_from_db[$i] == $start_time) {
					$start_time = date(TIME_DB_FULL, timezone_adjustment(to_timestamp($start_time) + $section_duration));
					$section_amount--;
				} else {
					array_push($disabled_another, $disabled_from_db[$i]);
				}
			}
			$disabled_from_db = array();
			// adjust end if needed
			for ($i=count($disabled_another) - 1; $i >= 0; $i--) {
				if ($disabled_another[$i] == $last_section) {
					$last_section = date(TIME_DB_FULL, timezone_adjustment(to_timestamp($last_section) - $section_duration));
					$section_amount--;
				} else {
					array_push($disabled_from_db, $disabled_another[$i]);
				}
			}
			// place this back to DB or DELETE consultation if everything was disabled
			if ($section_amount > 0) {
				kon_db('UPDATE kon_consultation SET start_time="' . $start_time . '", section_amount=' . $section_amount . ', disabled_sections="' . implode(',', $disabled_from_db) . '" WHERE id=' . $_POST['target']);
				if (empty($kicked_for_history))
					update_kon_history($_POST['target'], '|lang,ajax,disabledSec|' . implode(', ', $disabled_sections));
				else
					update_kon_history($_POST['target'], '|lang,ajax,disabledSecKick|' . implode(', ', $disabled_sections) . '|' . implode(', ', $kicked_for_history));
				prepare_notification('kon_section_del', $_POST['target'], $current_user, [ 'sections' => $disabled_sections ]);
			} else {
				prepare_notification('kon_delete', $_POST['target'], $current_user);
				kon_db('DELETE FROM kon_consultation WHERE id=' . $_POST['target']);
			}
		}
	}

	require 'pre_show_consultations.php';
}

// edits note of single consultation
function kon_edit_note () {
	if (kon_editable_section($_POST['target']) > 0) {
		kon_db('UPDATE kon_consultation SET kantor_note="' . addslashes($_POST['note']) . '" WHERE id=' . $_POST['target']);
		if ($_POST['note'] === '')
			update_kon_history($_POST['target'], '|lang,ajax,deletedNote');
		else
			update_kon_history($_POST['target'], '|lang,ajax,editedNote|' . history_encode(addslashes($_POST['note'])));
		prepare_notification('kon_note_change', $_POST['target'], null, [ 'pozn' => $_POST['note'] ]);
	}
	touch_user_action(get_logged_user()['login']);
}

// edits room of single consultation
function kon_edit_room () {
	if (kon_editable_section($_POST['target']) > 0) {
		kon_db('UPDATE kon_consultation SET room="' . addslashes($_POST['room']) . '" WHERE id=' . $_POST['target']);
		if ($_POST['room'] === '')
			update_kon_history($_POST['target'], '|lang,ajax,deletedRoom');
		else
			update_kon_history($_POST['target'], '|lang,ajax,editedRoom|' . history_encode(addslashes($_POST['room'])));
	}
	touch_user_action(get_logged_user()['login']);
}


// adds single section to single constulation and echoes new section
function kon_add_section () {
	$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $_POST['target'])->fetch_assoc();
	$one_user = null;
	$start_time = to_timestamp($kon_row['start_time']);
	$section_time = to_timestamp($kon_row['section_duration']);
	$disabled = array();
	$occupied = array();
	$is_current_author = true;
	$section_edit_num = kon_editable_section($_POST['target']);

	if ($_POST['top'] != 0) {
		if (kon_editable_section($_POST['target']) != 1)
			return;
		$si = 0;
		$start_time -= $section_time;
		if (timezone_adjustment($start_time + to_timestamp('00:00:00', $kon_row['execution_date'])) <= time())
			return;
	} else {
		if (kon_editable_section($_POST['target']) < 0)
			return;
		$si = $kon_row['section_amount'];
	}

	// check if new consultation would exceed 24 hours
	$sc = explode(':', $kon_row['section_duration']);
	if (($sc[0] * 60 + $sc[1]) * ($kon_row['section_amount'] + 1) >= 60 * 24)
		return;

	// check db for collisions
	$kon_from = $start_time + to_timestamp('00:00:00', $kon_row['execution_date']);
	$kon_to = $kon_from + $section_time * ($si == 0 ? $kon_row['section_amount'] : $kon_row['section_amount'] + 1);
	$comparement = kon_db('SELECT * FROM kon_consultation WHERE author_id="' . $kon_row['author_id'] . '" AND id<>' . $_POST['target']);
	while (($row = $comparement->fetch_assoc()) != null) {
		$r_from = to_timestamp($row['start_time'], $row['execution_date']);
		$r_to = $r_from + (to_timestamp($row['section_duration']) * $row['section_amount']);
		// check if two intervals overlap
		if ($kon_from < $r_to && $r_from < $kon_to) {
			return;
		}
	}

	kon_db('UPDATE kon_consultation SET start_time="' . date(TIME_DB_FULL, timezone_adjustment($start_time)) . '", section_amount="' . (1 + $kon_row['section_amount']) . '" WHERE id=' . $_POST['target']);

	$current_user = get_logged_user();
	touch_user_action($current_user['login']);

	if ($_POST['top'] != 0)
		$new_section = date(TIME_S, timezone_adjustment($start_time));
	else
		$new_section = date(TIME_S, timezone_adjustment($start_time + ($kon_row['section_amount'] + 1) * $section_time));
	update_kon_history($_POST['target'], '|lang,ajax,addedSec|' . $new_section);
	prepare_notification('kon_section_add', $_POST['target'], $current_user, [ 'sections' => [ $new_section ] ]);

	require 'single_section.php';
}

// returns consultation author data by id
function get_kantor_by_id () {
	echo json_encode(kon_db('SELECT email, first_name, last_name, titles_before, titles_after FROM kon_user JOIN kon_consultation ON kon_user.login=kon_consultation.author_id WHERE kon_consultation.id=' . $_POST['target'])->fetch_assoc());
}

// returns list of kantors from database
function get_kantor_list () {
	$result = kon_db('SELECT * FROM kon_user WHERE level>1 ORDER BY last_name, first_name');
	$toWrite = [];
	$filter = false;
	if (isset($_GET['kfilter']))
		$filter = urldecode($_GET['kfilter']);
	while (($row = $result->fetch_assoc()) != null) {
		if ($filter && !strpos($row['email'], $filter))
			continue;
		array_push($toWrite, [
			'label' => ($row['titles_before'] ? $row['titles_before'] . ' ' : '') . $row['last_name'] . ' ' . $row['first_name'] . ($row['titles_after'] ? ', ' . $row['titles_after'] : ''),
			'login' => $row['login'],
			'value' => $row['login']
		]);
	}
	echo json_encode($toWrite);
}

// returns list of domains of e-mail adresses of registered kantors
function get_kantor_filter () {
	$result = kon_db('SELECT DISTINCT (SUBSTR(email, INSTR(email, \'@\') + 1)) FROM kon_user' . (isset($_POST['for_stud']) ? '' : ' WHERE level>1'));
	$toWrite = [];
	// add option to disable filter if already set
	if (!isset($_POST['for_stud']) && isset($_GET['kfilter']))
		array_push($toWrite, [
			'label' => $GLOBALS['lang']->ajax->all,
			'value' => ' '
		]);
	while (($row = $result->fetch_array()) != null) {
		array_push($toWrite, [
			'label' => $row[0],
			'value' => urlencode($row[0])
		]);
	}
	echo json_encode($toWrite);
}

// returns list of years that chosen kantor has consultations in
function get_year_range_list () {
	if (isset($_POST['wanted']) && !empty($_POST['wanted']))
		$wanted = $_POST['wanted'];
	else
		$wanted = get_logged_user()['login'];
	$result = kon_db('SELECT * FROM kon_consultation WHERE author_id="' . $wanted . '" ORDER BY execution_date DESC, start_time DESC');
	$years = [];
	while (($row = $result->fetch_assoc()) != null) {
		$y = substr($row['execution_date'], 0, 4);
		if (!in_array($y, $years))
			array_push($years, $y);
	}
	// add next year for date filter
	if (!empty($years))
		array_unshift($years, (string)(intval($years[0]) + 1));
	$toWrite = [
		[ 'label' => $GLOBALS['lang']->ajax->lastWeek, 'value' => date(DATE_DB, strtotime('-1 week')) ],
		[ 'label' => $GLOBALS['lang']->ajax->lastMonth, 'value' => date(DATE_DB, strtotime('-1 month')) ]
	];
	array_push($toWrite, [ 'label' => $GLOBALS['lang']->ajax->now, 'value' => '' ]);
	foreach ($years as $value) {
		array_push($toWrite, [
			'label' => $value,
			'value' => $value . '-01-01'
		]);
	}
	echo json_encode($toWrite);
}

// returns histroy row of targeted consultation
function get_kon_history () {
	$rows = history_encode(explode('|', kon_db('SELECT history FROM kon_consultation WHERE id=' . $_POST['target'])->fetch_assoc()['history']), true);
	$toWrite = '<div class="container-fluid">';
	// expected format: [DATE] [TIME] - [USER_LOGIN] |[STRING_JSON_PATH]|[ADDITIONAL_PARAMETERS]
	// - e.g.: 2018-01-01 12:00:00 - myName@email.com |lang,ajax,addedSec|14:30
	for ($i=0; $i < count($rows) - 1; $i++) {
		$toWrite .= '<div class="row"><div class="col-sm-4">' . strtok($rows[$i], ' ') . ' ' . strtok(' ') . '</div><div class="col-sm-8">' . strtok('|');
		// get the json path string
		$ajaxstr = explode(',', strtok('|'));
		// get other parameters if there are some
		$the_rest = [];
		$one_param = strtok('|');
		while ($one_param) {
			array_push($the_rest, history_encode($one_param, true));
			$one_param = strtok('|');
		}
		// get to the desired sentence in json
		$temp_path = $GLOBALS[$ajaxstr[0]];
		for ($j=1; $j < count($ajaxstr); $j++) { 
			$temp_path = $temp_path->{$ajaxstr[$j]};
		}
		array_unshift($the_rest, $temp_path);
		$toWrite .= call_user_func_array('repl_str', $the_rest) . '</div></div>';
	}
	echo $toWrite . '</div>';
}

// signs user to chosen section in chosen consultation
function sign_to_kon () {
	$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $_POST['target'])->fetch_assoc();
	$start_time = to_timestamp($kon_row['start_time']);
	$section_time = to_timestamp($kon_row['section_duration']);
	$disabled = array();
	$occupied = $kon_row['occupied_sections'];
	if (empty($occupied))
		$occupied = array();
	else
		$occupied = json_decode($occupied, true);
	$si = 0;
	$chosen_time = to_timestamp($_POST['section'] . ':00');
	$section_edit_num = kon_editable_section($_POST['target']);
	while (($start_time + ($section_time * $si)) % (60*60*24) != $chosen_time) {
		$si++;
	}

	$available = kon_editable_section($_POST['target']);
	if ($available < 0 || $available > $si + 1)
		return;

	$current_user = get_logged_user();
	if (!$current_user)
		return;
	touch_user_action($current_user['login']);

	// do nothing if user is filtered (currently possible only by forging ajax requests)
	if (!empty($kon_row['stud_filter']) && !in_array(explode('@', $current_user['email'])[1], explode(',', $kon_row['stud_filter'])))
		return false;
	// do nothing if section is occupied (currently possible only by forging ajax requests)
	if (array_key_exists($_POST['section'], $occupied))
		return;

	update_kon_history($_POST['target'], '|lang,ajax,signedToKon');
	prepare_notification('student_login', $_POST['target'], $current_user, [ 'sections' => [ $_POST['section'] ] ]);

	kon_db('INSERT INTO kon_signed (id,login,note,section) VALUES(' . $_POST['target'] . ',"' . $current_user['login'] . '","","' . $_POST['section'] . ':00")');
	// notifikace
	$fields = explode(',', $current_user['notif_defaults_stud']);
	for ($i=0; $i < count($fields); $i++) { 
		$fields[$i] .= ':1';
	}
	$_POST['fields'] = implode(',', $fields);
	kon_notifications_change();

	$one_user = kon_db('SELECT * FROM kon_signed NATURAL JOIN kon_user WHERE id=' . $_POST['target'] . ' AND section="' . $_POST['section'] . ':00"')->fetch_assoc();
	require 'single_section.php';
}

// signs user out from chosen section in chosen consultation
function signout_from_kon () {
	$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $_POST['target'])->fetch_assoc();
	$one_user = null;
	$start_time = to_timestamp($kon_row['start_time']);
	$section_time = to_timestamp($kon_row['section_duration']);
	$disabled = array();
	$occupied = array();
	$si = 0;
	$chosen_time = to_timestamp($_POST['section'] . ':00');
	$section_edit_num = kon_editable_section($_POST['target']);
	while (($start_time + ($section_time * $si)) % (60*60*24) != $chosen_time) {
		$si++;
	}

	$current_user = get_logged_user();
	touch_user_action($current_user['login']);

	$available = kon_editable_section($_POST['target']);
	if ($available < 0 || $available > $si + 1)
		return;

	// apology
	if ($_POST['message'] != '' && $_POST['to_send'] == 'y')
		send_message(repl_str($GLOBALS['lang']->messages->studLogoutApolMsg, $current_user['first_name'], $current_user['last_name'], $current_user['email'], $_POST['message']),
			kon_db('SELECT email FROM kon_user WHERE login="' . $kon_row['author_id'] . '"')->fetch_assoc()['email'],
			$GLOBALS['lang']->messages->studLogoutApolSubj,
			$_POST['target']);

	update_kon_history($_POST['target'], '|lang,ajax,signedOff');
	if ((to_timestamp($kon_row['start_time'], $kon_row['execution_date']) + ($section_time * $si)) - time() < LAST_MINUTE) {
		$current_user['section'] = $_POST['section'];
		prepare_notification('student_last_min_logout', $_POST['target'], $current_user, [ 'users' => [ $current_user ] ]);
	} else
		prepare_notification('student_logout', $_POST['target'], $current_user, [ 'sections' => [ $_POST['section'] ] ]);

	kon_db('DELETE FROM kon_signed WHERE id=' . $_POST['target'] . ' AND login="' . $current_user['login'] . '" AND section="' . $_POST['section'] . ':00"');
	// notifikace
	$fields = explode(',', $current_user['notif_defaults_stud']);
	for ($i=0; $i < count($fields); $i++) { 
		$fields[$i] .= ':0';
	}
	$_POST['fields'] = implode(',', $fields);
	kon_notifications_change();

	require 'single_section.php';
}

// edits signed user note
function user_edit_note () {
	$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $_POST['target'])->fetch_assoc();
	$start_time = to_timestamp($kon_row['start_time']);
	$section_time = to_timestamp($kon_row['section_duration']);
	$chosen_time = to_timestamp($_POST['section'] . ':00');
	$si = 0;
	while ($start_time + ($section_time * $si) != $chosen_time) {
		$si++;
	}
	$available = kon_editable_section($_POST['target']);
	if ($available < 0 || $available > $si + 1)
		return;

	$current_user = get_logged_user();
	touch_user_action($current_user['login']);

	update_kon_history($_POST['target'], '|lang,ajax,studNote|' . history_encode(addslashes($_POST['note'])));
	prepare_notification('student_note_change', $_POST['target'], $current_user, [ 'pozn' => $_POST['note'], 'section' => $_POST['section'] ]);

	kon_db('UPDATE kon_signed SET note="' . addslashes($_POST['note']) . '" WHERE id=' . $_POST['target'] . ' AND login="' . $current_user['login'] . '" AND section="' . $_POST['section'] . ':00"');
}

// sends message to selected users/kantor
function kon_send_message () {
	if (empty($_POST['recipients']))
		return;
	if ($_POST['from_stud'] > 0) {
		$subj = $GLOBALS['lang']->messages->fromStudSubj;
	} else {
		$subj = $GLOBALS['lang']->messages->fromKantSubj;
	}

	$current_user = get_logged_user();
	touch_user_action($current_user['login']);
	
	if (send_message($_POST['message'], $_POST['recipients'], $subj, $_POST['target']))
		echo 'ok';
}

// returns notification settings for chosen consultation for current user
function kon_get_user_notifications () {
	$login = get_logged_user()['login'];
	touch_user_action($login);

	global $notif_fields;

	if (kon_db('SELECT author_id FROM kon_consultation WHERE id=' . $_POST['target'])->fetch_assoc()['author_id'] == $login)
		$is_author = true;
	else
		$is_author = false;

	$notifications = kon_db('SELECT * FROM kon_notifications WHERE id=' . $_POST['target'])->fetch_assoc();
	$toWrite = '';
	foreach ($notif_fields as $value) {
		if (!$is_author && isset($value['just_author']) && $value['just_author'])
			continue;
		if ($is_author && isset($value['just_not_author']) && $value['just_not_author'])
			continue;
		$is_checked = '';
		if (in_array($login, explode(',', $notifications[$value['name']])))
			$is_checked = ' checked';
		$toWrite .= '<div><label class="checkbox-label"><input type="checkbox" value="' . $value['name'] . '"' . $is_checked . '>' . $value['val'] . '</label></div>';
	}
	echo $toWrite;
}

// changes users notification setting for chosen consultation
function kon_notifications_change () {
	$login = get_logged_user()['login'];
	touch_user_action($login);

	$notifications = kon_db('SELECT * FROM kon_notifications WHERE id=' . $_POST['target'])->fetch_assoc();
	$fields = explode(',', $_POST['fields']);
	$sql = 'UPDATE kon_notifications SET ';
	foreach ($fields as $val) {
		// fields correspond to database column names
		$parts = explode(':', $val);
		$olds = $notifications[$parts[0]];
		if ($olds)
			$olds = explode(',', $olds);
		else
			$olds = [];
		$is_in = array_search($login, $olds);
		if ($parts[1] == 0 && $is_in !== false)
			array_splice($olds, $is_in);
		else if ($parts[1] == 1 && $is_in === false)
			array_push($olds, $login);
		$sql .= $parts[0] . '="' . implode(',', $olds) . '",';
	}
	$sql = rtrim($sql, ',') . ' WHERE id=' . $notifications['id'];
	kon_db($sql);
}

// returns notification fileds for new consiltation creation form
function kon_just_notif_fields () {
	global $notif_fields;
	$toWrite = '';
	$result = kon_db('SELECT notif_defaults_kant FROM kon_user WHERE login="' . get_logged_user()['login'] . '"')->fetch_assoc()['notif_defaults_kant'];
	if (!empty($result))
		$result = explode(',', $result);
	else
		$result = [];
	foreach ($notif_fields as $value) {
		if (isset($value['just_not_author']) && $value['just_not_author'])
			continue;
		$toWrite .= '<div><label class="checkbox-label"><input type="checkbox" value="' . $value['name'] . (in_array($value['name'], $result) ? '" checked>' : '">') . $value['val'] . '</label></div>';
	}
	echo $toWrite;
}

// checks if sent values are in correct format
function check_input_format () {
	if (isset($_POST['email'])) {
		if (empty($_POST['email']))
			echo $GLOBALS['lang']->errors->required;
		else if (!verify_email_pattern($_POST['email']))
			echo $GLOBALS['lang']->errors->badMailFormat;
		else if (!$_POST['for_reset'] && kon_db('SELECT login FROM kon_user WHERE login="' . $_POST['email'] . '"')->fetch_assoc() != null) {
			echo $GLOBALS['lang']->errors->exist;
		}

	} else if (isset($_POST['pass1']) || isset($_POST['pass2'])) {
		$toWrite = '';
		if (!empty($_POST['pass1']))
			$comparer = 'pass1';
		else if (!empty($_POST['pass2']))
			$comparer = 'pass2';
		if (strlen($_POST[$comparer]) < 6)
			$toWrite = $comparer . '|' . $GLOBALS['lang']->errors->passMinLen;
		else if (strlen($_POST[$comparer]) > 61)
			$toWrite = $comparer . '|' . $GLOBALS['lang']->errors->passMaxLen;
		if ($toWrite == '' && !empty($_POST['pass1']) && !empty($_POST['pass2']) && $_POST['pass1'] != $_POST['pass2'])
			$toWrite = 'pass1|pass2|' . $GLOBALS['lang']->errors->passDontMatch;
		echo $toWrite;

	} else if (isset($_POST['name'])) {
		if (empty($_POST['name']))
			echo $GLOBALS['lang']->errors->required;
		else if (!verify_name_pattern($_POST['name']))
			echo $GLOBALS['lang']->errors->justLetters;
	
	} else if (isset($_POST['titles']) && !empty($_POST['titles']) && !verify_title_pattern($_POST['titles']))
		echo $GLOBALS['lang']->errors->badTitle;
}

// change account level
function change_acc_level () {
	$login = get_logged_user()['login'];
	touch_user_action($login);
	kon_db('UPDATE kon_user SET level=' . $_POST['changeto'] . ' WHERE login="' . $login . '"');
}

// checks user password and if correct, save new one
function submit_new_pass () {
	$current_user = get_logged_user();
	touch_user_action($current_user['login']);

	if (empty($_POST['pass_orig']))
		echo $GLOBALS['lang']->errors->actualPass;
	else if (!password_verify($_POST['pass_orig'], $current_user['pass']))
		echo $GLOBALS['lang']->errors->badPass;
	else if (empty($_POST['pass1']) || (strlen($_POST['pass1']) < 6))
		echo ' ';
	else if (strlen($_POST['pass1']) > 61)
		echo ' ';
	else if ($_POST['pass1'] != $_POST['pass2'])
		echo ' ';
	else {
		kon_db('UPDATE kon_user SET pass="' . password_hash($_POST['pass1'], PASSWORD_BCRYPT) . '", acc_state="ok" WHERE login="' . $current_user['login'] . '"');
		$_SESSION['top_messages'] = $GLOBALS['lang']->infoMsg->passChanged;
	}
}

// enables or disables section by author text (occupy)
function kon_occupy_section () {
	if (kon_editable_section($_POST['target']) > 0) {
		$current_user = get_logged_user();
		$secs = json_decode(kon_db('SELECT occupied_sections FROM kon_consultation WHERE id=' . $_POST['target'])->fetch_assoc()['occupied_sections'], true);
		if (empty($secs))
			$secs = array();
		if (!empty($_POST['note'])) {
			$secs[$_POST['section']] = $_POST['note'];
			update_kon_history($_POST['target'], '|lang,ajax,occupiedSec|' . $_POST['section'] . '|' . history_encode(addslashes($_POST['note'])));
		} else if (array_key_exists($_POST['section'], $secs)) {
			unset($secs[$_POST['section']]);
			update_kon_history($_POST['target'], '|lang,ajax,unoccupiedSec|' . $_POST['section']);
		}
		kon_db('UPDATE kon_consultation SET occupied_sections="' . addslashes(json_encode($secs)) . '" WHERE id=' . $_POST['target']);
		prepare_notification('kon_section_add', $_POST['target'], $current_user, [ 'sections' => [ $_POST['section'] ] ]);
	}
	touch_user_action($current_user['login']);
}

// update stud. filter
function kon_update_stud_filter () {
	kon_db('UPDATE kon_consultation SET stud_filter="' . $_POST['stud_filter'] . '" WHERE id=' . $_POST['target']);
}

// sets selected language
function change_language () {
	setcookie('language', $_POST['lang'], time() + REMEMBERME_DURATION);
}

// feedback
function save_feedback () {
	$f = fopen('feedback.txt', 'a');
	fwrite($f, $_POST['text'] . "\r\n");
	fclose($f);
}

function stop_showing_feedback () {
	setcookie('feedback_info', 1, time() + REMEMBERME_DURATION);
}