<?php

require_once('functions.php');

global $current_user;
$current_user = get_logged_user();

check_POST();

if (isset($_POST['nav_to_log']) || isset($_POST['logreg'])) {
	require 'login.php';
	exit;
}

if ($current_user) {
	prolong_login_cookie();
	touch_user_action($current_user['login']);
}
$title = APP_NAME;
require('header.php');


if (isset($_GET['kantor']))
	$wanted_user = kon_db('SELECT * FROM kon_user WHERE login="' . urldecode($_GET['kantor']) . '"')->fetch_assoc();
else
	$wanted_user = $current_user;

handle_from_param();

?>

<!--

	TODO

při duplikaci přidat možnost na duplikaci prihlasenych

do editačního režimu přidat editaci data -> upozornit pokud už je někdo přihlášen

počet pokusů pro heslo

info na HP - představit aplikaci

gravatar

cookies save recent kantors on top of kantor search list

kon_room adjust width

new kon create end padding

password change in profile settings - error messages uncircusify

HTTPS - musí provozovatel appky vyřešit individuálně

možnost skrýt kantorovi prvky pro přihlášení a vyhledávání cizích konzultací - nastavení profilu

přejmenovat aplikaci aby měla fajn anglický název
-->

<?php echo print_info_messages(); ?>
	<div id="main_content" class="container-fluid">

		<div class="row" id="top_menu">
			<div class="profile_frame col-sm-2">
<?php if ($current_user) { ?>				
				<div><b><?php echo $current_user['first_name'] . ' ' . $current_user['last_name']; ?></b></div>
				<div class="dropdown">
					<button type="button" class="dropbtn"><?php echo $lang->index->menu; ?></button>
					<div class="dropcont">
						<a href="<?php echo HOME_URL; ?>/profile.php"><i class="glyphicon glyphicon-cog"></i>&nbsp;&nbsp;<?php echo $lang->index->settings; ?></a>
						<a href="?logout"><i class="glyphicon glyphicon-log-out"></i>&nbsp;&nbsp;<?php echo $lang->index->logout; ?></a>
					</div>
				</div>
<?php } else { ?>
				<form action="" method="post">
					<input type="hidden" name="nav_to_log">
					<label>
						<span>&nbsp;</span>
						<button type="submit"><?php echo $lang->login->loginBut; ?></button>
					</label>
				</form>
<?php } ?>				
			</div>

			<div id="kantor_selection" class="col-sm-6">
				<label class="kan_sel_input"><span><?php echo $lang->other->lector; ?></span><input type="text" value="<?php if ($wanted_user) echo ($wanted_user['titles_before'] ? $wanted_user['titles_before'] . ' ' : '') . $wanted_user['last_name'] . ' ' . $wanted_user['first_name'] . ($wanted_user['titles_after'] ? ', ' . $wanted_user['titles_after'] : '') . ' <' . $wanted_user['email'] . '>'; ?>"></label>
				<label class="kan_sel_filter"><span><?php echo $lang->index->filter; ?></span><input type="text" value="<?php if (isset($_GET['kfilter'])) echo urldecode($_GET['kfilter']); ?>"></label>
<?php if ($current_user) { ?>				
				<a class="look-like-button" href="<?php echo HOME_URL;?>"><?php echo $lang->index->myConsults; ?></a>
<?php } ?>				
			</div>
<?php if ($wanted_user) { ?>
			<div id="daterange_selection" class="col-sm-4">
				<label title="<?php echo $lang->index->inFormat; ?>">
					<span><?php echo $lang->index->filterByTime; ?></span>
					<div class="time_from">
						<span><?php echo $lang->index->from; ?></span>
						<input type="text"<?php echo (isset($_GET['from']) ? ' value="' . $_GET['from'] . '"' : '') ?>>
					</div>
					<div class="time_to">
						<span><?php echo $lang->index->to; ?></span>
						<input type="text"<?php echo (isset($_GET['to']) ? ' value="' . $_GET['to'] . '"' : '') ?>>
					</div>
				</label>
			</div>
<?php } ?>
		</div>
		<div class="main_error_container"></div>

		<div class="container<?php if (!$current_user) echo ' unlogged'; ?>" id="main_container">
<?php require 'pre_show_consultations.php'; ?>
		</div>

		<div id="scroll_to_top">
			<div>
				<button type="button"><span class="glyphicon glyphicon-arrow-up"></span></button>
			</div>
		</div>


<?php if ($current_user && $current_user['login'] == $wanted_user['login'] && $current_user['level'] >= KANTOR_LEVEL) { ?>
		<div id="new_kon_modal" class="modal fade" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<button type="button" class="modal-close-button" data-dismiss="modal">x</button>				
					<div id="new_kon_form" class="container-fluid">
						<div class="row sections">
							<div class="col-sm-6">
								<div class="date_selection">
									<div class="desc"><?php echo $lang->index->date; ?></div>
									<input type="text" id="new_kon_datepicker">
								</div>
								<div class="time_selection">
									<div class="desc"><?php echo $lang->index->start; ?></div>
									<input type="text" id="time_sel_field" class="value_assist" maxlength="5" value="<?php echo (isset($_COOKIE['val_start']) ? $_COOKIE['val_start'] : '15:00') ?>" data-step="30" data-type="time">
								</div>
							</div>

							<div class="section_settings col-sm-6">
								<div>
									<div class="desc"><?php echo $lang->index->secDur; ?></div>
									<input type="text" id="section_dur_field" class="value_assist" maxlength="5" value="<?php echo (isset($_COOKIE['val_sec_dur']) ? $_COOKIE['val_sec_dur'] : '0:30') ?>" data-step="5" data-type="time">
								</div>
								<div>
									<div class="desc"><?php echo $lang->index->secNum; ?></div>
									<input type="text" id="section_num_field" class="value_assist" maxlength="2" value="<?php echo (isset($_COOKIE['val_sec_num']) ? $_COOKIE['val_sec_num'] : '4') ?>" data-min="1">
								</div>
							</div>
						</div>

						<div class="kon_end row">
							<div class="col-sm-6">
								<div class="desc"><?php echo $lang->index->end; ?></div>
								<div id="kon_end_calculated"></div>
							</div>
							<div class="col-sm-6">
								<div class="desc"><?php echo $lang->index->room; ?></div>
								<input type="text" id="consult_room" value="<?php echo $current_user['room']; ?>">
							</div>
						</div>

						<div class="kon_note">
							<div class="desc"><?php echo $lang->index->note; ?></div>
							<textarea id="kon_note_field" rows="2"></textarea>
						</div>

						<div class="kon_notif_choice">
							<div class="desc"><?php echo $lang->index->notif; ?></div>
							<div class="notif_options_area"></div>
						</div>

						<div class="kon_stud_filter">
							<div class="desc"><?php echo $lang->consultation->restriction; ?></div>
							<div class="tagarea"></div>
							<input type="text">
						</div>

						<div class="error_area"></div>

						<div class="kon_create_button">
							<button type="button" class="btn-yes"><?php echo $lang->index->create; ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php } ?>

		<div id="modal_confirm_dialog" class="modal fade" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<button type="button" class="modal-close-button" data-dismiss="modal">x</button>
					<div class="modal-message"></div>
					<div class="choose-buttons">
						<button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog"><?php echo $lang->other->close; ?></button>
					</div>
				</div>
			</div>
		</div>

	</div>

<?php if (!isset($_COOKIE['feedback_info'])) { ?>
		<div class="feedback_note">
			<button class="feed_close">x</button>
			Děkuji za používání mé aplikace. Moc mi to pomůže, při jejím dalším vývoji. Mějte prosím na paměti, že aplikace je stále ve vývoji.<br>
			Pokud budete mít jakékoliv připomínky či dotazy, využíjte tlačítko <b>Feedback</b> v pravém dolním rohu obrazovky anebo mi napište e-mail na <a href="mailto:xbalaj03@stud.fit.vutbr.cz">xbalaj03@stud.fit.vutbr.cz</a>. Děkuji.
		</div>
<?php } ?>

<?php require('footer.php'); ?>