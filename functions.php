<?php

// constants
// warning: do NOT put whitespace characters to formats
define('DATE_CZ', 'j.n.Y');
define('DATE_DB', 'Y-m-d');
define('TIME_S', 'G:i');
define('TIME_DB', 'H:i');
define('TIME_DB_FULL', 'H:i:s');
define('DATE_WITH_TIME', DATE_DB . ' ' . TIME_DB_FULL);

define('COOKIE_CRYPT_METHOD', 'AES128');
define('COOKEY', 'UKBeiw94hvbdehIl');
define('KEYDATE', 'Y-m-d;H:i:s');

define('KANTOR_LEVEL', 2);

define('LAST_MINUTE', 60*60*2);
define('ACTION_CHECK_DELAY', 60);
define('REMEMBERME_DURATION', 3600*24*90);

// FEEDBACK > 0 shows feedback message and UI to send feedback
define('FEEDBACK', 1);

// database credentials and local settings
require 'local_settings.php';

// load language file
if (isset($_COOKIE['language']))
	$fname = $_COOKIE['language'];
else
	$fname = DEFAULT_LANG;
global $lang;
try {
	$lang = json_decode(file_get_contents('lang/' . $fname . '.json'));
} catch (Exception $e) {
	setcookie('language', '', time() - 3600);
	$lang = json_decode(file_get_contents('lang/' . DEFAULT_LANG . '.json'));
}

// replaces variable/constant placeholders in string with actual variables/constants
// !! to replace variables, they need to be supplied as additional parameters
function repl_str ($str) {
	// get additional variables to replace
	$arr = func_get_args();
	$matches = [];
	preg_match_all('/{(.+?)}/', $str, $matches);
	foreach (array_unique($matches[1]) as $value) {
		if ($value[0] == '$')
			$str = str_replace('{' . $value . '}', $arr[substr($value, 1)], $str);
		else
			$str = str_replace('{' . $value . '}', constant($value), $str);
	}
	return $str;
}

// used in kon_get_user_notifications() & kon_just_notif_fields()
global $notif_fields;
$notif_fields = [
	[ 'name' => 'kon_delete', 'val' => $lang->functions->notifKonDel, 'just_not_author' => true ],
	[ 'name' => 'kon_note_change', 'val' => $lang->functions->notifNoteChange, 'just_not_author' => true ],
	[ 'name' => 'student_note_change', 'val' => $lang->functions->notifStudNoteChange ],
	[ 'name' => 'kon_section_del', 'val' => $lang->functions->notifSecDel, 'just_not_author' => true ],
	[ 'name' => 'kon_section_add', 'val' => $lang->functions->notifSecAdd, 'just_not_author' => true ],
	[ 'name' => 'student_login', 'val' => $lang->functions->notifStudLogin ],
	[ 'name' => 'student_last_min_logout', 'val' => repl_str($lang->functions->notifLastMinLogout, LAST_MINUTE/3600), 'just_author' => true ],
	[ 'name' => 'student_logout', 'val' => $lang->functions->notifStudLogout ]
];

// executes given sql query (can be raw)
// if $close is different from 0, cloces database connection after executing sql query
function kon_db ($sql, $close = 0) {

	global $conn;
	if (!isset($conn)) {
		$conn = new mysqli(db_servername, db_username, db_password, db_dbname, 0, '/var/run/mysql/mysql.sock');
		if ($conn->connect_error)
			trigger_error('Connection failed: ' . $conn->connect_error, E_USER_ERROR);
	}

	$conn->set_charset("utf8");
	$stmnt = $conn->prepare($sql);

	if ($stmnt === false)
		trigger_error('Error with query: "' . $sql . '" >> ' . $conn->error, E_USER_ERROR);
	try {
		if ($stmnt->execute() === false)
			trigger_error('Error with query: "' . $sql . '" >> ' . $stmnt->error, E_USER_ERROR);
	} catch (Exception $e) {
		// in case of "Statement needs to be re-prepared" error
		$stmnt = $conn->prepare($sql);
		if ($stmnt === false)
			trigger_error('Error with query: "' . $sql . '" >> ' . $conn->error, E_USER_ERROR);
		if ($stmnt->execute() === false)
			trigger_error('Error with query: "' . $sql . '" >> ' . $stmnt->error, E_USER_ERROR);
	}

	$result = $stmnt->get_result();
	if ($stmnt->error != '') {
		trigger_error('Error with query: "' . $sql . '" >> ' . $stmnt->error, E_USER_ERROR);
	}

	if ($close != 0) {
		$conn->close();
		unset($conn);
	}

	return $result;
}

// with help of strtotime() transforms given string to timestamp
function to_timestamp ($to_transform, $date='1970-01-01') {
	return strtotime($date . ' ' . $to_transform . ' UTC');
}

// adjust given timestamp to work properly with current timezone
function timezone_adjustment ($timestamp) {
	if (date('I') == '0')
		return $timestamp - date('Z');
	else
		return $timestamp - date('Z') + 3600;
}

// checks if $nick contains only letters and numbers
function verify_login_pattern ($nick) {
	if (preg_match('/^[a-z0-9]+$/i', $nick) != 1)
		return false;
	else
		return true;
}

// checks if $nick contains only letters
function verify_name_pattern ($nick) {
	if (preg_match('/^[a-záčďéěíňóřšťúůýžÁČĎÉĚÍŇÓŘŠŤÚÝŽäöüßÄÖÜẞ \-]+$/i', $nick) != 1)
		return false;
	else
		return true;
}

// checks if $email is valid e-mail address
function verify_email_pattern ($email) {
	$mail_pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';
	if (preg_match($mail_pattern, $email) != 1)
		return false;
	else
		return true;
}

// checks if $title contains allowed characters to be in title
function verify_title_pattern ($title) {
	if (preg_match('/^[a-z\., ]+$/i', $title) != 1)
		return false;
	else
		return true;
}

// create database tables if they dont exist yet
function create_tables_if_not_exist () {

	$sql = 'SHOW TABLES LIKE "kon_user"';
	$result = kon_db($sql);
	if ($result->num_rows < 1) {
		$sql = 'CREATE TABLE kon_user(
			login varchar(100) PRIMARY KEY,
			pass varchar(100),
			first_name varchar(100),
			last_name varchar(100),
			titles_before varchar(100) DEFAULT "",
			titles_after varchar(100) DEFAULT "",
			email varchar(100),
			level int(4) DEFAULT 1,
			room varchar(100) DEFAULT "",
			hash_for_pass varchar(100),
			acc_state varchar(20),
			acc_creation_time datetime DEFAULT CURRENT_TIMESTAMP,
			last_activity datetime DEFAULT CURRENT_TIMESTAMP,
			actions_check_time datetime DEFAULT CURRENT_TIMESTAMP,
			queued_actions text,
			notif_defaults_kant text,
			notif_defaults_stud text,
			show_to_all int(2) DEFAULT 1,
			stud_show int(2) DEFAULT 1
		) COLLATE=utf8mb4_unicode_520_ci';
		kon_db($sql);
	}

	$sql = 'SHOW TABLES LIKE "kon_consultation"';
	$result = kon_db($sql);
	if ($result->num_rows < 1) {
		$sql = 'CREATE TABLE kon_consultation(
			id int AUTO_INCREMENT PRIMARY KEY,
			author_id varchar(100),
			execution_date date,
			creation_time datetime DEFAULT CURRENT_TIMESTAMP,
			kantor_note text,
			start_time time,
			section_duration time,
			section_amount int(8),
			disabled_sections varchar(255),
			occupied_sections text,
			history text,
			stud_filter text,
			room varchar(100)
		) COLLATE=utf8mb4_unicode_520_ci';
		kon_db($sql);
		kon_db('ALTER TABLE kon_consultation ADD( FOREIGN KEY (author_id) references kon_user(login))');
	}

	$sql = 'SHOW TABLES LIKE "kon_notifications"';
	$result = kon_db($sql);
	if ($result->num_rows < 1) {
		$sql = 'CREATE TABLE kon_notifications(
			id int PRIMARY KEY,
			student_login text,
			student_logout text,
			student_last_min_logout text,
			kon_note_change text,
			student_note_change text,
			kon_section_add text,
			kon_section_del text,
			kon_delete text
		) COLLATE=utf8mb4_unicode_520_ci';
		kon_db($sql);
		kon_db('ALTER TABLE kon_notifications ADD( FOREIGN KEY (id) references kon_consultation(id))');
	}

	$sql = 'SHOW TABLES LIKE "kon_signed"';
	$result = kon_db($sql);
	if ($result->num_rows < 1) {
		$sql = 'CREATE TABLE kon_signed(
			id int,
			login varchar(100),
			section time,
			sign_time datetime DEFAULT CURRENT_TIMESTAMP,
			note text
		) COLLATE=utf8mb4_unicode_520_ci';
		kon_db($sql);
		kon_db('ALTER TABLE kon_signed ADD( PRIMARY KEY (id, login, section) )');
		kon_db('ALTER TABLE kon_signed ADD( FOREIGN KEY (id) references kon_consultation(id) on delete cascade)');
		kon_db('ALTER TABLE kon_signed ADD( FOREIGN KEY (login) references kon_user(login))');
	}

	// triggers
	global $conn;
	$conn->query('CREATE TRIGGER `notif_add` AFTER INSERT ON `kon_consultation`
			FOR EACH ROW
				INSERT INTO kon_notifications (id) VALUES (new.id)');

	$conn->query('CREATE TRIGGER `notif_del` BEFORE DELETE ON `kon_consultation`
			FOR EACH ROW
				DELETE FROM kon_notifications WHERE id=old.id');
}

// sets crypted login cookie so the app can identify, if and who is logged in
function create_login_cookie ($username, $remember=false) {
	$now = time();
	$expiration = $now + 3600 * 2;
	if (isset($remember) && $remember != false) {
		$expiration += REMEMBERME_DURATION;
		setcookie('login_remember', $expiration, $expiration);
	}
	setcookie('login_time', $now, $expiration);
	setcookie('login_user', openssl_encrypt($username, COOKIE_CRYPT_METHOD, COOKEY . date(KEYDATE, $now), 0, COOKEY), $expiration);
}

// prolongs user cookie by another 2 hours from now
function prolong_login_cookie () {
	$now = time();
	$expiration = $now + 3600 * 2;
	if (isset($_COOKIE['login_remember']) && $_COOKIE['login_remember'] > $expiration)
		return;
	if (isset($_COOKIE['login_time']) && isset($_COOKIE['login_user'])) {
		setcookie('login_time', $_COOKIE['login_time'], $expiration);
		setcookie('login_user', $_COOKIE['login_user'], $expiration);
	}
}

// deletes login cookie of user currently logged in
function delete_login_cookie () {
	$expiration = time() - 3600;
	if (isset($_COOKIE['login_time'])) {
		setcookie('login_time', '', $expiration);
		unset($_COOKIE['login_time']);
	}
	if (isset($_COOKIE['login_user'])) {
		setcookie('login_user', '', $expiration);
		unset($_COOKIE['login_user']);
	}
	if (isset($_COOKIE['login_remember'])) {
		setcookie('login_remember', '', $expiration);
		unset($_COOKIE['login_remember']);
	}
}

// if user is logged in, returns his data from database in associative array, otherwise returns false
function get_logged_user () {
	if (isset($_COOKIE['login_time']) && isset($_COOKIE['login_user'])) {
		$login = openssl_decrypt($_COOKIE['login_user'], COOKIE_CRYPT_METHOD, COOKEY . date(KEYDATE, $_COOKIE['login_time']), 0, COOKEY);
		$result = kon_db('SELECT * FROM kon_user WHERE login="' . $login . '"');
		return $result->fetch_assoc();
	} else return false;
}

// updates last user action time in database
function touch_user_action ($login) {
	kon_db('UPDATE kon_user SET last_activity=CURRENT_TIMESTAMP WHERE login="' . $login . '"');
}

// returns printed $errors and $top_messages (also from $_SESSION['errors'], $_SESSION['top_messages'])
function print_info_messages () {
	global $errors, $top_messages;
	$toWrite = '';

	if (isset($_SESSION) && isset($_SESSION['top_messages'])) {
		array_push($top_messages, $_SESSION['top_messages']);
		unset($_SESSION['top_messages']);
	}
	if (isset($_SESSION) && isset($_SESSION['errors'])) {
		array_push($top_messages, $_SESSION['errors']);
		unset($_SESSION['errors']);
	}

	if (!empty($top_messages)) {
		$toWrite .= '<div class="top_messages">';
		foreach ($top_messages as $value) {
			$toWrite .= '<p>' . $value . '</p><span class="close_button">x</span>';
		}
		$toWrite .= '</div>';
		$top_messages = [];
	}
	if (!empty($errors)) {
		$toWrite .= '<div class="errors">';
		foreach ($errors as $value) {
			$toWrite .= '<p>' . $value . '</p><span class="close_button">x</span>';
		}
		$toWrite .= '</div>';
		$errors = [];
	}
	return $toWrite;
}

// encodes/decodes certain characters in string
function history_encode ($str, $decode=null) {
	if ($decode)
		return str_replace('%1', '%', str_replace('%2', '|', $str));
	else
		return str_replace('|', '%2', str_replace('%', '%1', $str));
}

// updates history row in consultation by adding $note at the end
// - automatically prepends current timestamp and current user
function update_kon_history ($id, $note) {
	global $current_user;
	if (!isset($current_user))
		$current_user = get_logged_user();

	$toWrite = date(DATE_WITH_TIME) . ' - ' . $current_user['login'] . ' ' . history_encode(addslashes($note)) . '|';
	kon_db('UPDATE kon_consultation SET history=CONCAT(history,"' . $toWrite . '") WHERE id=' . $id);
}

// checks and processes $_POST & $_GET
function check_POST () {
	global $errors, $top_messages, $current_user, $lang;
	$errors = [];
	$top_messages = [];

	// login
	if (isset($_POST['logreg']) && $_POST['logreg'] == 'log') {
		$result = kon_db('SELECT * FROM kon_user WHERE login="' . $_POST['email'] . '"')->fetch_assoc();
		if ($result == null || !password_verify($_POST['pass'], $result['pass']))
			array_push($errors, $lang->errors->badLogin);
		else if ($result['acc_state'] == 'verifying') {
			send_acc_verify_email($_POST['email']);
			array_push($errors, $lang->errors->unverifiedLogin);
		} else {
			if (isset($_POST['remember']))
				$remember = true;
			else
				$remember = false;
			create_login_cookie($_POST['email'], $remember);
			header('Refresh:0');
			exit;
		}

	// register
	} else if (isset($_POST['logreg']) && $_POST['logreg'] == 'reg') {
		if ($_POST['pass1'] !== $_POST['pass2'])
			array_push($errors, $lang->errors->passDontMatch);
		else if (strlen($_POST['pass1']) < 6)
			array_push($errors, $lang->errors->passMinLen);
		else if (strlen($_POST['pass1']) > 61)
			array_push($errors, $lang->errors->passMaxLen);

		if (!verify_name_pattern($_POST['firstname'])) {
			array_push($errors, $lang->errors->firstnameLetters);
			unset($_POST['firstname']);
		}

		if (!verify_name_pattern($_POST['lastname'])) {
			array_push($errors, $lang->errors->lastnameLetters);
			unset($_POST['lastname']);
		}

		if (!verify_email_pattern($_POST['email'])) {
			array_push($errors, $lang->errors->badMailFormat);
			unset($_POST['email']);
		}

		$additional_items = '';
		$additional_values = '';

		if (isset($_POST['addtitles']) && $_POST['addtitles'] == 'titles') {
			if (isset($_POST['titles_before']) && !empty($_POST['titles_before'])) {
				if (verify_title_pattern($_POST['titles_before'])) {
					$additional_items .= ',titles_before';
					$additional_values .= ',"' . $_POST['titles_before'] . '"';
				} else {
					array_push($errors, $lang->errors->badTitlesBefore);
					unset($_POST['titles_before']);
				}
			}
			if (isset($_POST['titles_after']) && !empty($_POST['titles_after']) && verify_title_pattern($_POST['titles_after'])) {
				if (verify_title_pattern($_POST['titles_after'])) {
					$additional_items .= ',titles_after';
					$additional_values .= ',"' . $_POST['titles_after'] . '"';
				} else {
					array_push($errors, $lang->errors->badTitlesAfter);
					unset($_POST['titles_after']);
				}
			}
		}

		if (isset($_POST['iskantor']) && $_POST['iskantor'] == 'kantor') {
			$additional_items .= ',level';
			$additional_values .= ',2';
			if (isset($_POST['room']) && !empty($_POST['room'])) {
				$additional_items .= ',room';
				$additional_values .= ',"' . addslashes($_POST['room']) . '"';
			}
		}

		if (!empty($errors))
			return;

		// check if exists
		$result = kon_db('SELECT login FROM kon_user WHERE login="' . $_POST['email'] . '"');
		while (($row = $result->fetch_assoc()) != null) {
			if ($row['login'] == $_POST['email']) {
				array_push($errors, $lang->errors->accWithMail);
				return;
			}
		}

		$pass = password_hash($_POST['pass1'], PASSWORD_BCRYPT);
		$hash_for_pass = md5(mt_rand());

		kon_db('INSERT INTO kon_user (login,pass,first_name,last_name,email' . $additional_items . ',hash_for_pass,acc_state,notif_defaults_stud,notif_defaults_kant) VALUES ("' . $_POST['email'] . '","' . $pass . '","' . $_POST['firstname'] . '","' . $_POST['lastname'] . '","' . $_POST['email'] . '"' . $additional_values . ',"' . $hash_for_pass . '","verifying","student_last_min_logout,kon_delete","student_last_min_logout")');

		send_acc_verify_email($_POST['email']);

		array_push($top_messages, $lang->infoMsg->mailToActivate);
		unset($_POST['logreg']);
	
	// logout
	} else if (isset($_GET['logout']) && $current_user) {
		touch_user_action($current_user['login']);
		delete_login_cookie();
		unset($current_user);
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		$_SESSION['top_messages'] = $lang->infoMsg->logout;
		header('Location: ' . HOME_URL);
		exit;
	
	// email account verification & login
	} else if (isset($_GET['verify']) && isset($_GET['hash'])) {
		$result = kon_db('SELECT * FROM kon_user WHERE login="' . urldecode($_GET['verify']) . '"')->fetch_assoc();
		if (!$result || $result['hash_for_pass'] != $_GET['hash'])
			return;
		if ($result['acc_state'] == 'verifying')
			kon_db('UPDATE kon_user SET acc_state="ok" WHERE login="' . $result['login'] . '"');
		else if ($result['acc_state'] == 'disabled')
			return;
		create_login_cookie($result['login']);
		header('Location: ' . HOME_URL);
		exit;
	}

}

// returns consultation section number (starting from 1) that is still editable (-1 if none)
function kon_editable_section ($konid, $return_time=false) {
	$result = kon_db('SELECT * FROM kon_consultation WHERE id=' . $konid)->fetch_assoc();
	$now = time();
	$kon_start = to_timestamp($result['start_time'], $result['execution_date']);
	$kon_sec_dur = to_timestamp($result['section_duration']);
	for ($i = 0; $i < $result['section_amount']; $i++) {
		if (timezone_adjustment($kon_start + $i * $kon_sec_dur) > $now)
			if ($return_time)
				return date(TIME_DB_FULL, timezone_adjustment($kon_start + $i * $kon_sec_dur));
			else
				return $i + 1;
	}
	return -1;
}

// checks and properly sets $_GET['from']
function handle_from_param () {
	if (!isset($_GET['from']))
		return;
	$val = $_GET['from'];
	if (strlen($val) == 4)
		$val .= '-01-01';
	else if (strlen($val) == 7)
		$val .= '-01';
	if (strtotime($val) == false)
		unset($val);
	else
		$_GET['from'] = $val;
}

// sends message to recipient(s)
function send_message ($msg, $recip, $subj, $konid) {
	if (empty($msg))
		return false;
	
	$result = kon_db('SELECT * FROM kon_consultation JOIN kon_user ON kon_consultation.author_id=kon_user.login WHERE id=' . $konid)->fetch_assoc();
	$current_user = get_logged_user();
	$exectime = date(DATE_CZ . ' ' . TIME_S, timezone_adjustment(to_timestamp($result['start_time'], $result['execution_date'])));

	$msg .= repl_str($GLOBALS['lang']->messages->sendMsgSignature, $current_user['last_name'], $current_user['first_name'], $result['id'], $exectime, $result['last_name'], $result['first_name']);

	$headers = 'From: ' . $current_user['email'] . "\r\n" .
				'Content-Type: text/plain; charset=utf-8' . "\r\n";

	$subj = "=?UTF-8?B?" . base64_encode($subj) . "?=";
	
	return mail($recip, $subj, $msg, $headers);
}

// sends verification e-mail
function send_acc_verify_email ($login) {
	$result = kon_db('SELECT * FROM kon_user WHERE login="' . $login . '"')->fetch_assoc();

	$verify_link = HOME_URL . '?verify=' . urlencode($result['login']) . '&hash=' . $result['hash_for_pass'];
	$subj = "=?UTF-8?B?" . base64_encode(repl_str($GLOBALS['lang']->messages->accVerifySubj)) . "?=";
	$msg = repl_str($GLOBALS['lang']->messages->accVerifyMsg, $verify_link);
	$headers = 'From: ' . APP_EMAIL . "\r\n" .
				'Content-Type: text/plain; charset=utf-8' . "\r\n";

	return mail($result['email'], $subj, $msg, $headers);
}

// sorts decoded json by time
function sortByTime ($a, $b) {
    return $a['time'] - $b['time'];
}

// records user action to database and waits some time before sending notifications
function prepare_notification ($type, $target, $user=null, $data=[]) {
	if (empty($user))
		$user = get_logged_user();

	// in case of "kon_delete" or "student_last_min_logout", skip planning and send right away
	if ($type == 'kon_delete' || $type == 'student_last_min_logout') {
		$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $target)->fetch_assoc();
		$subj = $GLOBALS['lang']->index->notif . ': ';
		if ($type == 'kon_delete') {
			$subj .= $GLOBALS['lang']->messages->konDeleteSubj;
			$msg = $GLOBALS['lang']->messages->konDeleteMsg . PHP_EOL;
		} else {
			$subj .= $GLOBALS['lang']->messages->studLogoutSubj;
			$msg = '';
			foreach ($data['users'] as $target_user) {
				$msg .= repl_str($GLOBALS['lang']->messages->studLogoutMsg, $target_user['first_name'], $target_user['last_name'], $target_user['email'], $target_user['section']);
			}
		}
		real_send_notification ($type, $subj, $msg, $kon_row);
		return;
	}

	$toWrite = [ 'type' => $type, 'target' => $target, 'data' => $data, 'time' => time() ];

	$queued = kon_db('SELECT queued_actions FROM kon_user WHERE login="' . $user['login'] . '"')->fetch_assoc()['queued_actions'];
	if (empty($queued))
		$queued = [];
	else
		$queued = json_decode($queued, true);
	array_push($queued, $toWrite);

	$exec_in = date(DATE_WITH_TIME, time() + ACTION_CHECK_DELAY);
	kon_db('UPDATE kon_user SET queued_actions="' . addslashes(json_encode($queued)) . '", actions_check_time="' . $exec_in . '" WHERE login="' . $user['login'] . '"');
}

// prepare data for sending notificatons
function before_send_notification ($target_user) {
	// first check if there are notifications to be sent and if they are already ready to be sent
	if (empty($target_user['queued_actions']) || strcmp(date(DATE_WITH_TIME), $target_user['actions_check_time']) < 0)
		return;
	$actions = json_decode($target_user['queued_actions'], true);
	kon_db('UPDATE kon_user SET queued_actions="" WHERE login="' . $target_user['login'] . '"');

	usort($actions, 'sortByTime');
	$logins = [];
	$kon_notes = [];
	$student_notes = [];
	$sections = [];
	// get rid of duplicates
	foreach ($actions as $value) {
		$Atyp = $value['type'];
		$Atar = $value['target'];
		$Adat = $value['data'];

		// login/logout
		if ($Atyp == 'student_login' || $Atyp == 'student_logout') {
			if (!isset($logins[$Atar]))
				$logins[$Atar] = [];
			foreach ($Adat['sections'] as $sec) {
				if (isset($logins[$Atar][$sec]))
					unset($logins[$Atar][$sec]);
				else
					$logins[$Atar][$sec] = $Atyp;
			}
		}

		// kon_note change
		else if ($Atyp == 'kon_note_change') {
			$kon_notes[$Atar] = $Adat['pozn'];
		}

		// student_note_change
		else if ($Atyp == 'student_note_change') {
			if (!isset($student_notes[$Atar]))
				$student_notes[$Atar] = [];
			$student_notes[$Atar][$Adat['section']] = $Adat['pozn'];
		}

		// kon_sections
		else if ($Atyp == 'kon_section_add' || $Atyp == 'kon_section_del') {
			if (!isset($sections[$Atar]))
				$sections[$Atar] = [];
			foreach ($Adat['sections'] as $sec) {
				if (isset($sections[$Atar][$sec]))
					unset($sections[$Atar][$sec]);
				else
					$sections[$Atar][$sec] = $Atyp;
			}
		}
	}

	// login/logout
	foreach ($logins as $key => $value) {
		if (empty($value))
			continue;
		$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $key)->fetch_assoc();
		$logged = [];
		$msg = '';
		$subj = $GLOBALS['lang']->index->notif . ': ';
		foreach ($value as $sec => $type) {
			$logged[$type] = true;
			if ($type == 'student_login')
				$msg .= repl_str($GLOBALS['lang']->messages->studLoginMsg, $target_user['first_name'], $target_user['last_name'], $target_user['email'], $sec);
			else
				$msg .= repl_str($GLOBALS['lang']->messages->studLogoutMsg, $target_user['first_name'], $target_user['last_name'], $target_user['email'], $sec);
		}
		if (isset($logged['student_login']) && isset($logged['student_logout'])) {
			$subj .= $GLOBALS['lang']->messages->studLoginoutSubj;
			$type = 'student_login,student_logout';
		} else if (isset($logged['student_login'])) {
			$subj .= $GLOBALS['lang']->messages->studLoginSubj;
			$type = 'student_login';
		} else {
			$subj .= $GLOBALS['lang']->messages->studLogoutSubj;
			$type = 'student_logout';
		}
		real_send_notification($type, $subj, $msg, $kon_row);
	}

	// kon_note
	foreach ($kon_notes as $key => $value) {
		$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $key)->fetch_assoc();
		$subj = $GLOBALS['lang']->index->notif . ': ' . $GLOBALS['lang']->messages->konNoteChangeSubj;
		$msg = repl_str($GLOBALS['lang']->messages->konNoteChangeMsg, addslashes($value));
		real_send_notification('kon_note_change', $subj, $msg, $kon_row);
	}

	// student_note
	foreach ($student_notes as $key => $value) {
		$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $key)->fetch_assoc();
		$msg = '';
		$subj = $GLOBALS['lang']->index->notif . ': ' . $GLOBALS['lang']->messages->studNoteChangeSubj;
		foreach ($value as $sec => $note) {
			$msg .= repl_str($GLOBALS['lang']->messages->studNoteChangeMsg, $target_user['first_name'], $target_user['last_name'], $target_user['email'], $sec, addslashes($note));
		}
		real_send_notification('student_note_change', $subj, $msg, $kon_row);
	}

	// section add/delete
	foreach ($sections as $key => $value) {
		if (empty($value))
			continue;
		$kon_row = kon_db('SELECT * FROM kon_consultation WHERE id=' . $key)->fetch_assoc();
		$adddel = [];
		$msg = '';
		$subj = 'Notifikace: ';
		foreach ($value as $sec => $type) {
			$adddel[$type] = true;
			if ($type == 'kon_section_add')
				$msg .= repl_str($GLOBALS['lang']->messages->konSecAddMsg, $sec);
			else
				$msg .= repl_str($GLOBALS['lang']->messages->konSecDelMsg, $sec);
		}
		if (isset($adddel['kon_section_add']) && isset($adddel['kon_section_del'])) {
			$subj .= $GLOBALS['lang']->messages->konSecAddDelSubj;
			$type = 'kon_section_add,kon_section_del';
		} else if (isset($adddel['kon_section_add'])) {
			$subj .= $GLOBALS['lang']->messages->konSecAddSubj;
			$type = 'kon_section_add';
		} else {
			$subj .= $GLOBALS['lang']->messages->konSecDelSubj;
			$type = 'kon_section_del';
		}
		real_send_notification($type, $subj, $msg, $kon_row);
	}
}

// sends notification
function real_send_notification ($type, $subj, $msg, $kon_row) {
	$subj = "=?UTF-8?B?" . base64_encode($subj) . "?=";
	$exectime = date(DATE_CZ . ' ' . TIME_S, timezone_adjustment(to_timestamp($kon_row['start_time'], $kon_row['execution_date'])));
	$kantor = kon_db('SELECT * FROM kon_user WHERE login="' . $kon_row['author_id'] . '"')->fetch_assoc();
	$msg .= repl_str($GLOBALS['lang']->messages->sendNotifSignature, $kon_row['id'], $exectime, $kantor['last_name'], $kantor['first_name']);

	if (strpos($type, 'student_last_min_logout') !== false)
		$type .= ',student_logout';
	$user_logins = implode(',', kon_db('SELECT ' . $type . ' FROM kon_notifications WHERE id=' . $kon_row['id'])->fetch_all()[0]);
	if (empty($user_logins))
		return;
	$user_logins = array_unique(explode(',', $user_logins));
	$sql = 'SELECT email FROM kon_user WHERE ';
	foreach ($user_logins as $value) {
		$sql .= 'login="' . $value . '" OR ';
	}
	$user_emails = kon_db(substr($sql, 0, -4))->fetch_all();

	$recip = [];
	foreach ($user_emails as $value) {
		array_push($recip, $value[0]);
	}
	$recip = implode(', ', $recip);

	$headers = 'From: ' . APP_EMAIL . "\r\n" .
				'Content-Type: text/plain; charset=utf-8' . "\r\n" .
				'To: Undisclosed Recipients <no-reply@' . explode('@', APP_EMAIL)[1] . '>' . "\r\n" .
				'BCC: ' . $recip . "\r\n";

	return mail($recip, $subj, $msg, $headers);
}

create_tables_if_not_exist();