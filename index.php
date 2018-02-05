<?php

require_once('functions.php');

global $current_user;
$current_user = get_logged_user();

check_POST();

if ($current_user) {
	prolong_login_cookie();
	touch_user_action($current_user['login']);
	$title = APP_NAME;
	require('header.php');
} else {
	require('login.php');
	exit;
}

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

návod na instalaci pro opensource - pro lokalizaci lze doplnit povolene znaky ve jmene, prijimeni,...

info na HP - představit aplikaci

gravatar

cookies save recent kantors on top of kantor search list

burza terminu

zpravy i primo v aplikaci (nejen e-maily)
-->
	
	<div id="main_content" class="container-fluid">

		<div class="row" id="top_menu">
			<div class="profile_frame col-sm-2">
				<div><b><?php echo $current_user['first_name'] . ' ' . $current_user['last_name']; ?></b></div>
				<div class="dropdown">
					<button type="button" class="dropbtn">MENU</button>
					<div class="dropcont">
						<a href="<?php echo HOME_URL; ?>/profile.php"><i class="glyphicon glyphicon-cog"></i>&nbsp;&nbsp;Nastavení</a>
						<a href="?logout"><i class="glyphicon glyphicon-log-out"></i>&nbsp;&nbsp;Odhlásit</a>
					</div>
				</div>
			</div>

			<div id="kantor_selection" class="col-sm-6">
				<label class="kan_sel_input"><span>Kantor</span><input type="text" value="<?php echo ($wanted_user['titles_before'] ? $wanted_user['titles_before'] . ' ' : '') . $wanted_user['last_name'] . ' ' . $wanted_user['first_name'] . ($wanted_user['titles_after'] ? ', ' . $wanted_user['titles_after'] : '') . ' <' . $wanted_user['email'] . '>'; ?>"></label>
				<label class="kan_sel_filter"><span>Filtr</span><input type="text" value="<?php if (isset($_GET['kfilter'])) echo urldecode($_GET['kfilter']); ?>"></label>
				<a class="look-like-button" href="<?php echo HOME_URL;?>">Mé konzultace</a>
			</div>

			<div id="daterange_selection" class="col-sm-4">
				<label title="Ve formátu YYYY-MM-DD nebo YYYY-MM nebo YYYY">
					<span>Filtrovat dle času</span>
					<div class="time_from">
						<span>Od</span>
						<input type="text"<?php echo (isset($_GET['from']) ? ' value="' . $_GET['from'] . '"' : '') ?>>
					</div>
					<div class="time_to">
						<span>Do</span>
						<input type="text"<?php echo (isset($_GET['to']) ? ' value="' . $_GET['to'] . '"' : '') ?>>
					</div>
				</label>
			</div>
		</div>

		<div class="main_error_container"></div>

		<div class="container" id="main_container">
<?php require 'pre_show_consultations.php'; ?>
		</div>

		<div id="scroll_to_top">
			<div>
				<button type="button"><span class="glyphicon glyphicon-arrow-up"></span></button>
			</div>
		</div>


<?php if ($current_user['login'] == $wanted_user['login'] && $current_user['level'] >= KANTOR_LEVEL) { ?>
		<div id="new_kon_modal" class="modal fade" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<button type="button" class="modal-close-button" data-dismiss="modal">x</button>				
					<div id="new_kon_form" class="container-fluid">
						<div class="row sections">
							<div class="col-sm-6">
								<div class="date_selection">
									<div class="desc">Datum</div>
									<input type="text" id="new_kon_datepicker">
								</div>
								<div class="time_selection">
									<div class="desc">Začátek</div>
									<input type="text" id="time_sel_field" class="value_assist" maxlength="5" value="<?php echo (isset($_COOKIE['val_start']) ? $_COOKIE['val_start'] : '15:00') ?>" data-step="30" data-type="time">
								</div>
							</div>

							<div class="section_settings col-sm-6">
								<div>
									<div class="desc">Trvání úseku</div>
									<input type="text" id="section_dur_field" class="value_assist" maxlength="5" value="<?php echo (isset($_COOKIE['val_sec_dur']) ? $_COOKIE['val_sec_dur'] : '0:30') ?>" data-step="5" data-type="time">
								</div>
								<div>
									<div class="desc">Počet úseků</div>
									<input type="text" id="section_num_field" class="value_assist" maxlength="2" value="<?php echo (isset($_COOKIE['val_sec_num']) ? $_COOKIE['val_sec_num'] : '4') ?>" data-min="1">
								</div>
							</div>
						</div>

						<div class="kon_end row">
							<div class="col-sm-6">
								<div class="desc">Konec</div>
								<div id="kon_end_calculated"></div>
							</div>
						</div>

						<div class="kon_note">
							<div class="desc">Poznámka</div>
							<textarea id="kon_note_field" rows="2"></textarea>
						</div>

						<div class="kon_notif_choice">
							<div class="desc">Notifikace</div>
							<div class="notif_options_area"></div>
						</div>

						<div class="kon_stud_filter">
							<div class="desc">Povolit přihlášení pouze studentům s určitým e-mailem</div>
							<div class="tagarea"></div>
							<input type="text">
						</div>

						<div class="error_area"></div>

						<div class="kon_create_button">
							<button type="button" class="btn-yes">Vytvořit</button>
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
						<button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog">Zavřít</button>
					</div>
				</div>
			</div>
		</div>

	</div>

<?php require('footer.php'); ?>