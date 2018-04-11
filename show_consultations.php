<?php
/*
 * Show consultations
 *
 * parameters provided via url ( $_GET )
 *
 * supported MySQL date range: '1000-01-01' to '9999-12-31'
 *
 */

	if (!isset($current_user))
		$current_user = get_logged_user();

	if ($current_user && $wanted_user['login'] == $current_user['login'])
		$is_current_author = true;
	else
		$is_current_author = false;

	if (isset($_GET['from']))
		$show_from_date = date(DATE_DB, strtotime($_GET['from']));
	else
		$show_from_date = date(DATE_DB, strtotime('-1 day'));
	if (isset($_GET['to']))
		$show_to_date = date(DATE_DB, strtotime($_GET['to']));
	else
		$show_to_date = '9999-12-31';

	if ((isset($kantor_signed) && $kantor_signed) || ($is_current_author && $current_user['level'] < KANTOR_LEVEL)) {
		$is_current_author = false;
		$result = kon_db('SELECT * FROM kon_consultation NATURAL JOIN kon_signed WHERE login="' . $current_user['login'] . '" AND execution_date>="' . $show_from_date . '" AND execution_date<="' . $show_to_date . '" ORDER BY execution_date, start_time');
	} else
		$result = kon_db('SELECT * FROM kon_consultation WHERE author_id="' . $wanted_user['login'] . '" AND execution_date>="' . $show_from_date . '" AND execution_date<="' . $show_to_date . '" ORDER BY execution_date, start_time');

	$last_kon_id = null;
	if ($current_user || $wanted_user['show_to_all'] > 0)
		foreach ($result->fetch_all(MYSQLI_ASSOC) as $kon_field) {
			if ($last_kon_id == $kon_field['id'])
				continue;
			else
				$last_kon_id = $kon_field['id'];
			$signed = kon_db('SELECT * FROM kon_signed NATURAL JOIN kon_user WHERE id=' . $kon_field['id'] . ' ORDER BY section');
			$available = kon_editable_section($kon_field['id']);

			// check if current user is allowed to kon
			$stud_filter_arr = [];
			if (!empty($kon_field['stud_filter']))
				$stud_filter_arr = explode(',', $kon_field['stud_filter']);
			$filtered_user = false;
			if (!$is_current_author && !empty($kon_field['stud_filter'])) {
				if ($current_user)
					$filtered_user = !in_array(explode('@', $current_user['email'])[1], $stud_filter_arr);
				else
					$filtered_user = true;
			}

			// check if kon is already finished
			$now = time();
			$compare_time = to_timestamp($kon_field['start_time'], $kon_field['execution_date']) + ($kon_field['section_amount'] * to_timestamp($kon_field['section_duration']));
			if ($now >= $compare_time)
				$kon_is_past = ' is_past';
			else
				$kon_is_past = '';
?>
			
			<div class="row konzultace<?php if ($is_current_author) echo ' is_author'; echo $kon_is_past; ?>" id="kon_id_<?php echo $kon_field['id']; ?>" data-konid="<?php echo $kon_field['id']; ?>">
<?php	if (!isset($_GET['kantor']) && ($current_user['level'] < KANTOR_LEVEL || isset($kantor_signed) && $kantor_signed)) { 
			$kon_author = kon_db('SELECT * FROM kon_user, kon_consultation WHERE login=author_id AND id="' . $kon_field['id'] . '"')->fetch_assoc();
?>
				<div class="col-sm-12 kantor_name"><?php echo $GLOBALS['lang']->other->lector . ': <a href="' . HOME_URL . '/?kantor=' . $kon_author['login'] . '">' . (!empty($kon_author['titles_before']) ? $kon_author['titles_before'] . ' ' : '') . $kon_author['last_name'] . ' ' . $kon_author['first_name'] . (!empty($kon_author['titles_after']) ? ', ' . $kon_author['titles_after'] : '') . '</a>'; ?></div>
<?php	} ?>
				<div class="col-sm-3">
					<div class="datum">
						<span class="den"><?php echo $GLOBALS['lang']->consultation->days[date('N', strtotime($kon_field['execution_date']))]; ?></span>
						<span class="datum-format"><?php echo date(DATE_CZ, strtotime($kon_field['execution_date'])); ?></span>
					</div>
					<div class="underdatum">
<?php
		$kon_date = strtotime($kon_field['execution_date']);
		$datediff = floor(($kon_date - $now) / (60*60*24));

		if ($datediff < -1)
			echo $GLOBALS['lang']->consultation->past;
		else if ($datediff < 0)
			echo $GLOBALS['lang']->consultation->today;
		else if ($datediff < 1)
			echo $GLOBALS['lang']->consultation->tomorow;
		else if ($datediff < 4)
			echo repl_str($GLOBALS['lang']->consultation->inFewDays, ($datediff + 1));
		else if ($datediff < 6)
			echo repl_str($GLOBALS['lang']->consultation->inSeveralDays, ($datediff + 1));
		else if ($datediff < 13)
			echo $GLOBALS['lang']->consultation->inWeek;
		else if ($datediff < 30)
			echo repl_str($GLOBALS['lang']->consultation->inFewWeeks, floor(($datediff + 1) / 7));
		else
			echo $GLOBALS['lang']->consultation->inMonth;
?>
					</div>
				</div>
				<div class="col-sm-9 kon_right_part">
					<div class="kon-menu">
<?php if ($is_current_author && $available > 0) { ?>
						<button class="btn_disable" title="<?php echo $GLOBALS['lang']->consultation->titleDisable; ?>"><span class="glyphicon glyphicon-remove"></span></button>
						<button class="btn_enable" title="<?php echo $GLOBALS['lang']->consultation->titleEnable; ?>"><span class="glyphicon glyphicon-ok"></span></button>
<?php } if ($current_user) { ?>
						<button class="btn_message" title="<?php echo $GLOBALS['lang']->consultation->titleMessage; ?>"><span class="glyphicon glyphicon-envelope"></span></button>
<?php } if ($current_user && $available > 0) { ?>
						<button class="btn_notifications" title="<?php echo $GLOBALS['lang']->consultation->titleNotif; ?>"><span class="glyphicon glyphicon-bell"></span></button>
<?php } if ($is_current_author && $available > 0) { ?>
						<button class="btn_edit" title="<?php echo $GLOBALS['lang']->consultation->titleEdit; ?>"><span class="glyphicon glyphicon-pencil"></span></button>
<?php } if ($is_current_author) { ?>
						<button class="btn_duplicate" title="<?php echo $GLOBALS['lang']->consultation->titleDupli; ?>"><span class="glyphicon glyphicon-duplicate"></span></button>
<?php } ?>
						<button class="btn_history" title="<?php echo $GLOBALS['lang']->consultation->titleHistory; ?>"><span class="glyphicon glyphicon-info-sign"></span></button>
<?php if ($is_current_author && $available == 1) { ?>
						<button class="btn_delete" title="<?php echo $GLOBALS['lang']->consultation->titleDel; ?>"><span class="glyphicon glyphicon-trash"></span></button>
<?php } ?>
					</div>

					<div class="kon-popis">
						<div class="kon_room"<?php if (empty($kon_field['room'])) echo ' style="height: 0"'; ?>><?php echo '<b>' . $GLOBALS['lang']->index->room . ':</b> <span>' . $kon_field['room'] . '</span>'; ?></div>
						<div class="kon_descr"><?php echo $kon_field['kantor_note']; ?></div>
					</div>
<?php if ($is_current_author) { ?>
					<div class="kon-row-add top-add">
						<button title="<?php echo $GLOBALS['lang']->consultation->add; ?>"><span class="glyphicon glyphicon-plus"></span></button>
					</div>
<?php }
		$one_user = $signed->fetch_assoc();
		$start_time = to_timestamp($kon_field['start_time']);
		$section_time = to_timestamp($kon_field['section_duration']);
		$disabled = explode(',', $kon_field['disabled_sections']);
		$occupied = json_decode($kon_field['occupied_sections'], true);
		if (empty($occupied))
			$occupied = array();
		$section_edit_num = $available;
		for ($si=0; $si < $kon_field['section_amount']; $si++) {

			require 'single_section.php';

			if ($logged)
				$one_user = $signed->fetch_assoc();
		} // for 

	if ($is_current_author) { ?>
					<div class="kon-row-add bot-add">
						<button title="<?php echo $GLOBALS['lang']->consultation->add; ?>"><span class="glyphicon glyphicon-plus"></span></button>
					</div>
					<div class="kon_stud_filter">
						<div class="desc"><?php echo $GLOBALS['lang']->consultation->restriction; ?></div>
						<div class="tagarea">
<?php foreach ($stud_filter_arr as $value) {
	echo '<div data-val="' . urlencode($value) . '">' . $value . '<span>x</span></div>';
} ?>
						</div>
						<input type="text">
					</div>
					<div class="kon-edit-finish">
						<button type="button"><?php echo $GLOBALS['lang']->consultation->done; ?></button>
					</div>
<?php } ?>
				</div>
<?php if ($filtered_user) { ?>
				<div class="col-sm-offset-3 col-sm-9 restriction_info"><i class="glyphicon glyphicon-alert"></i><?php echo repl_str($GLOBALS['lang']->consultation->restricted, implode(', ', $stud_filter_arr)); ?></div>
<?php } ?>
			</div>

<?php } // foreach 
	else { ?>
			<div class="not_public"><?php echo $GLOBALS['lang']->consultation->noPub; ?></div>
<?php } ?>