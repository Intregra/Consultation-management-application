<?php

require_once('functions.php');

global $current_user, $errors, $top_messages;
$current_user = get_logged_user();
if (!isset($errors))
	$errors = [];
if (!isset($top_messages))
	$top_messages = [];

if ($current_user) {
	prolong_login_cookie();
	touch_user_action($current_user['login']);
	$title = 'Profile | ' . APP_NAME;
	require('header.php');
} else {
	header('Location: ' . HOME_URL);
	exit;
}

if (isset($_POST['profile_edit'])) {
	if (!verify_name_pattern($_POST['firstname'])) {
		array_push($errors, $lang->errors->firstnameLetters);
	}
	if (!verify_name_pattern($_POST['lastname'])) {
		array_push($errors, $lang->errors->lastnameLetters);
	}
	if (!empty($_POST['titles_before']) && !verify_title_pattern($_POST['titles_before'])) {
		array_push($errors, $lang->errors->badTitlesBefore);
	}
	if (!empty($_POST['titles_after']) && !verify_title_pattern($_POST['titles_after'])) {
		array_push($errors, $lang->errors->badTitlesAfter);
	}

	if (!isset($_POST['notif_defaults_kant']))
		$notif_arr_kant = '';
	else
		$notif_arr_kant = implode(',', $_POST['notif_defaults_kant']);
	if (!isset($_POST['notif_defaults_stud']))
		$notif_arr_stud = '';
	else
		$notif_arr_stud = implode(',', $_POST['notif_defaults_stud']);

	if (empty($errors)) {
		kon_db('UPDATE kon_user SET first_name="' . $_POST['firstname'] . '", last_name="' . $_POST['lastname'] . '", titles_before="' . $_POST['titles_before'] . '", titles_after="' . $_POST['titles_after'] . '", notif_defaults_kant="' . $notif_arr_kant . '", notif_defaults_stud="' . $notif_arr_stud . (isset($_POST['room']) ? '", room="' . $_POST['room'] : '') . '", show_to_all=' . (isset($_POST['public_view']) ? '0' : '1') . ', stud_show=' . (isset($_POST['stud_show']) ? '0' : '1') . ' WHERE login="' . $current_user['login'] . '"');
		$current_user = get_logged_user();
		array_push($top_messages, $lang->infoMsg->changesMade);
	}
}
?>

<div class="login-bg"></div>
<div class="profile-fg">

<?php echo print_info_messages(); ?>

<a href="<?php echo HOME_URL; ?>" class="look-like-button back_button"><?php echo $lang->other->back; ?></a>

	<form action="" method="post" id="profile_edit" class="form-horizontal">
	<div class="container-fluid">

		<h2><?php echo $lang->profile->general; ?></h2>

		<div class="form-group">
			<label class="control-label col-sm-4" for="email"><?php echo $lang->login->email; ?></label>
			<div class="col-sm-8">
				<input type="text" name="email" value="<?php echo $current_user['login']; ?>" disabled>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="firstname"><?php echo $lang->login->firstname; ?></label>
			<div class="col-sm-8">
				<input type="text" name="firstname" value="<?php echo $current_user['first_name']; ?>">
				<div class="input-error"></div>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="lastname"><?php echo $lang->login->lastname; ?></label>
			<div class="col-sm-8">
				<input type="text" name="lastname" value="<?php echo $current_user['last_name']; ?>">
				<div class="input-error"></div>
			</div>
		</div>

		<div class="form-group titles">
			<label class="control-label col-sm-4" for="titles_before"><?php echo $lang->profile->titlesBefore; ?></label>
			<div class="col-sm-8">
				<input type="text" name="titles_before" value="<?php echo $current_user['titles_before']; ?>">
				<div class="input-error"></div>
			</div>
		</div>

		<div class="form-group titles">
			<label class="control-label col-sm-4" for="titles_after"><?php echo $lang->profile->titlesAfter; ?></label>
			<div class="col-sm-8">
				<input type="text" name="titles_after" value="<?php echo $current_user['titles_after']; ?>">
				<div class="input-error"></div>
			</div>
		</div>

<?php if ($current_user['level'] > 1) { ?>
		<div class="form-group">
			<label class="control-label col-sm-4" for="room"><?php echo $lang->login->office; ?></label>
			<div class="col-sm-8">
				<input type="text" name="room" value="<?php echo $current_user['room']; ?>">
				<div class="input-error"></div>
			</div>
		</div>
<?php } ?>

		<div class="form-group acc_change">
			<label class="control-label col-sm-4" for="level"><?php echo $lang->profile->accType; ?></label>
			<div class="col-sm-8">
				<input type="text" name="level" value="<?php echo ($current_user['level'] > 1 ? $lang->other->lector : $lang->other->student); ?>" disabled>
				<button type="button" data-toggle="modal" data-target="#modal_confirm_dialog"><?php echo $lang->profile->change; ?></button>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="acc_creation_time"><?php echo $lang->profile->accCreated; ?></label>
			<div class="col-sm-8">
				<input type="text" name="acc_creation_time" value="<?php echo $current_user['acc_creation_time']; ?>" disabled>
			</div>
		</div>

		<div class="form-group acc_change">
			<label class="control-label col-sm-4"><?php echo $lang->login->pass; ?></label>
			<div class="col-sm-8">
				<button type="button" data-toggle="modal" data-target="#modal_new_pass"><?php echo $lang->profile->changePass; ?></button>
			</div>
		</div>

<?php if ($current_user['level'] > 1) { ?>
		<div class="form-group public_view">
			<div class="col-sm-offset-4 col-sm-8">
				<div>
					<label class="checkbox-label"><input type="checkbox" name="public_view"<?php echo ($current_user['show_to_all'] > 0 ? '>' : ' checked>') . '<div>' . $lang->profile->publicView . '</div>'; ?></label>
				</div>
			</div>
		</div>

		<div class="form-group stud_show">
			<div class="col-sm-offset-4 col-sm-8">
				<div>
					<label class="checkbox-label"><input type="checkbox" name="stud_show"<?php echo ($current_user['stud_show'] > 0 ? '>' : ' checked>') . '<div>' . $lang->profile->studShow . '</div>'; ?></label>
				</div>
			</div>
		</div>
<?php } ?>
		<h2><?php echo $lang->index->notif; ?></h2>
		<div class="desc"><?php echo $lang->profile->notifDesc; ?></div>
		<h2><?php echo $lang->profile->notifMeWhen; ?></h2>
		<div>
			<div class="notif_options_area">
<?php
if ($current_user['level'] > 1) {
	echo '<h3>' . $lang->profile->notifCreating . '</h3>';
	$notif_exploded = explode(',', $current_user['notif_defaults_kant']);
	foreach ($notif_fields as $value) {
		if (isset($value['just_not_author']))
			continue;
		if (in_array($value['name'], $notif_exploded))
			$is_checked = ' checked';
		else
			$is_checked = '';
		echo '<div><label class="checkbox-label"><input type="checkbox" name="notif_defaults_kant[]" value="' . $value['name'] . '"' . $is_checked . '>' . $value['val'] . '</label></div>';
	}
} ?>
		
		<h3><?php echo $lang->profile->notifSigning; ?></h3>
<?php
	$notif_exploded = explode(',', $current_user['notif_defaults_stud']);
	foreach ($notif_fields as $value) {
		if (isset($value['just_author']))
			continue;
		if (in_array($value['name'], $notif_exploded))
			$is_checked = ' checked';
		else
			$is_checked = '';
		echo '<div><label class="checkbox-label"><input type="checkbox" name="notif_defaults_stud[]" value="' . $value['name'] . '"' . $is_checked . '>' . $value['val'] . '</label></div>';
	}
?>
			</div>
		</div>

		<input type="hidden" name="profile_edit">
		<button type="submit" class="save_button"><?php echo $lang->profile->save; ?></button>

	</div>
	</form>

	<div id="modal_confirm_dialog" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<button type="button" class="modal-close-button" data-dismiss="modal">x</button>
				<div class="modal-message">
					<div><b><?php echo $lang->profile->accChange; ?></b></div>
					<div><?php echo $lang->profile->accChangeDesc; ?></div>
				</div>
				<div class="choose-buttons">
<?php if ($current_user['level'] > 1) { ?>
					<button class="btn-yes change_level" data-changeto="1"><?php echo $lang->profile->accChangeStud; ?></button>
<?php } else { ?>
					<button class="btn-yes change_level" data-changeto="2"><?php echo $lang->profile->accChangeKant; ?></button>
<?php } ?>
					<button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog"><?php echo $lang->other->close; ?></button>
				</div>
			</div>
		</div>
	</div>

	<div id="modal_new_pass" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<button type="button" class="modal-close-button" data-dismiss="modal">x</button>
				<form action="" method="post" class="modal-message form-horizontal">
					<div class="container-fluid">
						<div class="desc">&nbsp;</div>

						<div class="form-group">
							<label class="control-label col-sm-4" for="pass_orig"><?php echo $lang->profile->passCurrent; ?></label>
							<div class="col-sm-6">
								<input type="password" name="pass_orig" required>
							</div>
						</div>

						<div class="desc">&nbsp;</div>

						<div class="form-group">
							<label class="control-label col-sm-4" for="pass1"><?php echo $lang->profile->passNew; ?></label>
							<div class="col-sm-6">
								<input type="password" name="pass1" required>
								<div class="input-error"></div>
							</div>
						</div>

						<div class="form-group">
							<label class="control-label col-sm-4" for="pass2"><?php echo $lang->profile->passNewAgain; ?></label>
							<div class="col-sm-6">
								<input type="password" name="pass2" required>
								<div class="input-error"></div>
							</div>
						</div>
					</div>

					<div class="error_pass_area"></div>

					<div class="choose-buttons">
						<button class="btn-yes submit_new_pass" type="button"><?php echo $lang->profile->change; ?></button>
						<button type="button" class="btn-storn" data-toggle="modal" data-target="#modal_new_pass"><?php echo $lang->other->close; ?></button>
					</div>
				</form>
			</div>
		</div>
	</div>

</div>

<?php require('footer.php') ?>