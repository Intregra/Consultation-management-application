<?php
/*
 * Preparation for show_consultations.php
 *
 * - used mainly for dividing kantor consultations into 'his created' and 'signed on'
 *
 */

	if (!isset($current_user))
		$current_user = get_logged_user();

	if (isset($_GET['kantor']))
		$wanted_user = kon_db('SELECT * FROM kon_user WHERE login="' . urldecode($_GET['kantor']) . '"')->fetch_assoc();
	else
		$wanted_user = $current_user;

	if (!$wanted_user) { ?>
			<div id="plain_unlogged">
				<div class="desc"><?php echo repl_str($GLOBALS['lang']->index->welcome, $GLOBALS['lang']->other->lector); ?></div>
				<form action="" method="post">
					<input type="hidden" name="nav_to_log">
					<button type="submit"><?php echo $lang->login->loginBut . ' / ' . $lang->login->regBut; ?></button>
				</form>
			</div>
<?php } else if ($current_user && $current_user['login'] == $wanted_user['login'] && $current_user['level'] >= KANTOR_LEVEL) { ?>
			<ul class="nav nav-tabs">
				<li class="active"><a data-toggle="tab" href="#created"><?php echo $GLOBALS['lang']->consultation->created; ?></a></li>
				<li><a data-toggle="tab" href="#signed"><?php echo $GLOBALS['lang']->consultation->signed; ?></a></li>
			</ul>
			<div class="tab-content">
				<div id="created" class="tab-pane fade in active">
					<div id="create_new">
						<button type="button" class="btn_new_kon" data-toggle="modal" data-target="#new_kon_modal"><span class="glyphicon glyphicon-plus"></span>&nbsp;<?php echo $GLOBALS['lang']->consultation->newKon; ?></button>
					</div>
					<?php require 'show_consultations.php'; ?>
				</div>
				<div id="signed" class="tab-pane fade">
					<?php $kantor_signed = true; require 'show_consultations.php'; ?>
				</div>
			</div>
<?php
	} else {
		require 'show_consultations.php';
	}
?>