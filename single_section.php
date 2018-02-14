<?php
/*
 * One row (section) of single consultation
 *
 * expected variables:
 * - $one_user 			- one row of result of database selection from joined tables kon_signed & kon_user
 * - $start_time 		- timestamp of starting time of consultation
 * - $section_time 		- timestamp of duration of single section
 * - $disabled 			- array of disabled sections
 * - $si 				- section index
 * - $is_current_author	- true in case this consultation is shown for its author
 * - $section_edit_num	- result of function kon_editable_section()
 *
 * sets variable:
 * - $logged 		- is true if someone is logged in current section, false otherwise
 * - $help_dis_var	- stores DB time of currently processed section and eventually stores if section is disabled
 *
 */

$help_dis_var = date(TIME_DB_FULL, timezone_adjustment($si * $section_time + $start_time));

if ($section_edit_num > 0 && $section_edit_num <= $si + 1)
	$section_is_editable = true;
else
	$section_is_editable = false;

if (!isset($is_current_author))
	$is_current_author = false;

if ($one_user == null || $one_user['section'] != $help_dis_var) {
	// noone is logged
	$logged = false;
	// check if section is disabled
	if (in_array($help_dis_var, $disabled))
		$help_dis_var = ' disabled';
	else
		$help_dis_var = '';
?>
		<div class="kon-row<?php echo $help_dis_var; if (!$section_is_editable) echo ' is_past'; ?>">
			<div class="sel-checkbox"><label></label><input type="checkbox"></div>
			<div class="cas"><?php echo date(TIME_S, timezone_adjustment($start_time + $section_time * $si)); ?></div>
			<div class="jmeno"><?php if (!$is_current_author && empty($help_dis_var) && $section_is_editable) echo '<span class="glyphicon glyphicon-copy"></span> ' . $GLOBALS['lang']->consultation->signIn ?></div>
			<div class="pozn"></div>
			<?php if (!empty($help_dis_var)) echo '<div class="dis_row_label">' . $GLOBALS['lang']->consultation->disabled . '</div>'; ?>
		</div>
<?php
} else {
	// section is equal, someone is logged
	$logged = true;

	if (!isset($current_user))
		$current_user = get_logged_user();

	if ($current_user['login'] == $one_user['login'])
		$is_this_user = ' logged';
	else
		$is_this_user = '';
?>
		<div class="kon-row user_present<?php echo $is_this_user; ?>" data-login="<?php echo $one_user['login']; ?>" data-email="<?php echo $one_user['email']; ?>">
			<div class="sel-checkbox"><label></label><input type="checkbox"></div>
			<div class="cas"><?php echo date(TIME_S, timezone_adjustment($start_time + $section_time * $si)); ?></div>
<?php if ($is_this_user != '' && $section_is_editable) { ?>
			<div class="sign_out"><span class="glyphicon glyphicon-remove"></span></div>
<?php } ?>
			<div class="jmeno"><?php echo $one_user['first_name'] . ' ' . $one_user['last_name']; ?></div>
<?php if ($is_this_user != '' && $section_is_editable) { ?>
			<div class="edit_pozn"><span class="glyphicon glyphicon-pencil"></span></div>
<?php } ?>
			<div class="pozn"><?php echo $one_user['note']; ?></div>
		</div>
<?php } // else ?>