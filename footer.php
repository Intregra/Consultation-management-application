<?php if (FEEDBACK > 0) { ?>
	<!-- feedback area -->
	<button class="feedback_button" data-toggle="modal" data-target="#modal_feedback"><?php echo $GLOBALS['lang']->other->feedbackTitle; ?></button>
	<div id="modal_feedback" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<button type="button" class="modal-close-button" data-dismiss="modal">x</button>
				<div class="subject_header"><?php echo $GLOBALS['lang']->other->feedbackDesc; ?></div>
				<textarea id="feedback_textarea" rows="3"></textarea>
				<button type="button" class="feedback_submit"><?php echo $GLOBALS['lang']->other->send; ?></button>
			</div>
		</div>
	</div>
<?php } ?>

	<!-- Google Font -->
	<script src="js/jquery-ui.min.js"></script>
	<script src="js/datepicker-cs.js"></script>
	<script src="js/script.js"></script>
</body>
</html>