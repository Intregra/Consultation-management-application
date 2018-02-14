<?php

require_once('functions.php');

global $errors, $top_messages;
$errors = [];
$top_messages = [];
$display_phase = 0;

if (isset($_POST) && isset($_POST['passres'])) {
	// send e-mail
	if ($_POST['passres'] == 'send_email') {
		if (!verify_email_pattern($_POST['email'])) {
			array_push($errors, $GLOBALS['lang']->errors->badMailFormat);
		} else {
			$result = kon_db('SELECT * FROM kon_user WHERE email="' . $_POST['email'] . '"')->fetch_assoc();
			if (!$result) {
				array_push($errors, $GLOBALS['lang']->errors->noAccWithMail);
			} else {
				// account with given e-mail exists
				$hash_for_pass = md5(mt_rand());
				kon_db('UPDATE kon_user SET acc_state="pass-reset", hash_for_pass="' . $hash_for_pass . '" WHERE login="' . $result['login'] . '"');

				$verify_link = HOME_URL . '/pass_reset?reset=' . urlencode($result['login']) . '&hash=' . $hash_for_pass;
				$subj = "=?UTF-8?B?" . base64_encode(repl_str($GLOBALS['lang']->messages->passResSubj)) . "?=";
				$msg = repl_str($GLOBALS['lang']->messages->passResMsg, $verify_link);
				$headers = 'From: ' . APP_EMAIL . "\r\n" .
							'Content-Type: text/plain; charset=utf-8' . "\r\n";

				mail($result['email'], $subj, $msg, $headers);
				array_push($top_messages, $GLOBALS['lang']->infoMsg->verifyMailSent);
				$display_phase = 1;
			}
		}
	
	// reset password
	} else if ($_POST['passres'] == 'set_pass') {
		if (isset($_GET) && isset($_GET['reset']) && isset($_GET['hash'])) {
			if ($_POST['pass1'] !== $_POST['pass2'])
				array_push($errors, $GLOBALS['lang']->errors->passDontMatch);
			else if (strlen($_POST['pass1']) < 6)
				array_push($errors, $GLOBALS['lang']->errors->passMinLen);
			else if (strlen($_POST['pass1']) > 61)
				array_push($errors, $GLOBALS['lang']->errors->passMaxLen);
			else {
				$result = kon_db('SELECT * FROM kon_user WHERE login="' . $_GET['reset'] . '"')->fetch_assoc();
				if ($result && $result['hash_for_pass'] == $_GET['hash']) {
					$new_pass = password_hash($_POST['pass1'], PASSWORD_BCRYPT);
					kon_db('UPDATE kon_user SET pass="' . $new_pass . '", acc_state="ok" WHERE login="' . $result['login'] . '"');
					if (session_status() == PHP_SESSION_NONE) {
						session_start();
					}
					$_SESSION['top_messages'] = $GLOBALS['lang']->infoMsg->passChanged;
					header('Location: ' . HOME_URL);
					exit;
				} else {
					$display_phase = -1;
				}
			}
			$display_phase = 2; // code gets here if reloaction fails
		} else {
			$display_phase = -1;
		}
	}

} else if (isset($_GET) && isset($_GET['reset']) && isset($_GET['hash'])) {
	$result = kon_db('SELECT * FROM kon_user WHERE login="' . urldecode($_GET['reset']) . '"')->fetch_assoc();
	if ($result && $result['acc_state'] == 'pass-reset' && $result['hash_for_pass'] == $_GET['hash'])
		$display_phase = 2;
	else {
		array_push($errors, $GLOBALS['lang']->errors->badURLRequest);
		$display_phase = -1;
	}
}

$title = 'Reset Pass | ' . APP_NAME;
require('header.php');
?>

<div class="login-bg"></div>
<div class="login-fg">

<?php echo print_info_messages(); ?>
	<form action="" method="post" id="pass-reset-form" class="form-horizontal">

<?php if ($display_phase < 0) { ?>

	<div class="desc"><?php echo $GLOBALS['lang']->errors->badRequest; ?></div>

<?php } else if ($display_phase == 2) { ?>
		<div class="desc"><?php echo $GLOBALS['lang']->passReset->enterNewPass; ?></div>
		
		<div class="form-group">
			<label class="control-label col-sm-4" for="pass1"><?php echo $GLOBALS['lang']->login->pass; ?></label>
			<div class="col-sm-8">
				<input type="password" name="pass1" required>
				<div class="input-error"></div>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="pass2"><?php echo $GLOBALS['lang']->login->passAgain; ?></label>
			<div class="col-sm-8">
				<input type="password" name="pass2" required>
				<div class="input-error"></div>
			</div>
		</div>

		<div>
			<input type="hidden" name="passres" value="set_pass">
			<button type="submit"><?php echo $GLOBALS['lang']->other->confirm; ?></button>
		</div>

		<div>
			<div><a href="<?php echo HOME_URL; ?>"><i class="glyphicon glyphicon-menu-left"></i><?php echo $GLOBALS['lang']->other->back; ?></a></div>
		</div>

<?php } else { ?>

		<div class="desc"><?php echo $GLOBALS['lang']->passReset->enterMail; ?></div>
		
		<div class="form-group">
			<label class="control-label col-sm-4" for="email"><?php echo $GLOBALS['lang']->login->email; ?></label>
			<div class="col-sm-8">
				<input type="text" name="email"<?php if (isset($_POST) && isset($_POST['email'])) echo ' value="' . $_POST['email'] . '"'; ?> required>
				<div class="input-error"></div>
			</div>
		</div>

		<div>
			<input type="hidden" name="passres" value="send_email">
			<button type="submit"><?php echo $GLOBALS['lang']->other->send; ?></button>
		</div>

		<div>
			<div><a href="<?php echo HOME_URL; ?>"><i class="glyphicon glyphicon-menu-left"></i><?php echo $GLOBALS['lang']->other->back; ?></a></div>
		</div>

<?php } ?>
	
	</form>
</div>

<?php
	require('footer.php');
?>