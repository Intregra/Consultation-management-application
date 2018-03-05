<?php
$title = 'Login | ' . APP_NAME;
require('header.php');
?>

<div class="login-bg"></div>
<div class="login-fg">

<?php echo print_info_messages(); ?>
	<div class="main_error_container"></div>

	<button class="rfr_by_js"><?php echo $lang->other->back; ?></button>

	<form action="" method="post" id="login-form" class="form-horizontal<?php if (isset($_POST['logreg']) && $_POST['logreg'] != 'log') echo ' switched-to-bg'; ?>">
		
		<div class="form-group">
			<label class="control-label col-sm-4" for="email"><?php echo $GLOBALS['lang']->login->email; ?></label>
			<div class="col-sm-8">
				<input type="text" name="email" <?php if (isset($_POST['email'])) echo 'value="' . $_POST['email'] . '"'; ?> required>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="pass"><?php echo $GLOBALS['lang']->login->pass; ?></label>
			<div class="col-sm-8">
				<input type="password" name="pass" required>
			</div>
		</div>

		<div class="form-group remember-me">
			<div class="col-sm-offset-4 col-sm-8">
				<div>
					<label class="checkbox-label"><input type="checkbox" name="remember" value="remember"><?php echo $GLOBALS['lang']->login->stayLogged; ?></label>
				</div>
			</div>
		</div>

		<div>
			<input type="hidden" name="logreg" value="log">
			<button type="submit"><?php echo $GLOBALS['lang']->login->loginBut; ?></button>
		</div>

		<div>
			<div><?php echo $GLOBALS['lang']->login->noAcc; ?> <button type="button" class="switch-forms look-like-link"><?php echo $GLOBALS['lang']->login->regLink; ?></button></div>
			<div><a href="<?php echo HOME_URL; ?>/pass_reset.php"><?php echo $GLOBALS['lang']->login->forgotPass; ?></a></div>
		</div>

	</form>

	<form action="" method="post" id="register-form" class="form-horizontal<?php if (!isset($_POST['logreg']) || $_POST['logreg'] != 'reg') echo ' switched-to-bg'; ?>">
		
		<div class="email_info">
			<i class="glyphicon glyphicon-alert"></i>
			<div><?php echo $GLOBALS['lang']->login->regInfo; ?></div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="email"><?php echo $GLOBALS['lang']->login->email; ?></label>
			<div class="col-sm-8">
				<input type="text" name="email" <?php if (isset($_POST['email'])) echo 'value="' . $_POST['email'] . '"'; ?> required>
				<div class="input-error"></div>
			</div>
		</div>

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

		<div class="form-group">
			<label class="control-label col-sm-4" for="firstname"><?php echo $GLOBALS['lang']->login->firstname; ?></label>
			<div class="col-sm-8">
				<input type="text" name="firstname" <?php if (isset($_POST['firstname'])) echo 'value="' . $_POST['firstname'] . '"'; ?> required>
				<div class="input-error"></div>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-sm-4" for="lastname"><?php echo $GLOBALS['lang']->login->lastname; ?></label>
			<div class="col-sm-8">
				<input type="text" name="lastname" <?php if (isset($_POST['lastname'])) echo 'value="' . $_POST['lastname'] . '"'; ?> required>
				<div class="input-error"></div>
			</div>
		</div>

		<div class="addtitles-class form-group">
			<div class="col-sm-offset-4 col-sm-8">
				<div>
					<label class="checkbox-label"><input type="checkbox" name="addtitles" value="titles" <?php if (isset($_POST['addtitles'])) echo 'checked'; ?>><?php echo $GLOBALS['lang']->login->addTitles; ?></label>
				</div>
			</div>
		</div>

		<div class="form-group titles">
			<label class="control-label col-sm-4" for="titles_before"><?php echo $GLOBALS['lang']->login->titlesBefore; ?></label>
			<div class="col-sm-8">
				<input type="text" name="titles_before" <?php if (isset($_POST['titles_before'])) echo 'value="' . $_POST['titles_before'] . '"'; ?>>
				<div class="input-error"></div>
			</div>
		</div>

		<div class="form-group titles">
			<label class="control-label col-sm-4" for="titles_after"><?php echo $GLOBALS['lang']->login->titlesAfter; ?></label>
			<div class="col-sm-8">
				<input type="text" name="titles_after" <?php if (isset($_POST['titles_after'])) echo 'value="' . $_POST['titles_after'] . '"'; ?>>
				<div class="input-error"></div>
			</div>
		</div>

		<div class="iskantor-class form-group">
			<div class="col-sm-offset-4 col-sm-8">
				<div>
					<label class="checkbox-label"><input type="checkbox" name="iskantor" value="kantor" <?php if (isset($_POST['iskantor'])) echo 'checked'; ?>><?php echo $GLOBALS['lang']->login->regAsKant; ?></label>
				</div>
			</div>
		</div>

		<div class="form-group room-class<?php if (isset($_POST['iskantor'])) echo ' room-class-visible'; ?>">
			<label class="control-label col-sm-4" for="room"><?php echo $GLOBALS['lang']->login->office; ?></label>
			<div class="col-sm-8">
				<input type="text" name="room" <?php if (isset($_POST['room'])) echo 'value="' . $_POST['room'] . '"'; ?>>
			</div>
		</div>

		<div>
			<input type="hidden" name="logreg" value="reg">
			<button type="submit"><?php echo $GLOBALS['lang']->login->regBut; ?></button>
		</div>

		<div>
			<div><?php echo $GLOBALS['lang']->login->haveAcc; ?> <button type="button" class="switch-forms look-like-link"><?php echo $GLOBALS['lang']->login->loginLink; ?></button></div>
		</div>

	</form>

</div>

<?php
	require('footer.php');
?>