 $( function() {
	$( "#new_kon_datepicker" ).datepicker({
		showWeek: true,
		firstDay: 1,
		regional: [lang.langISO],
		dateFormat: 'd.m.yy',
		showOtherMonths: true,
    	selectOtherMonths: true
	});

	// some variables are served from php in header.php
	var hide_show_row_anim_dur = 500;
	var note_send_delay = 2000;
	//var sec_tok = $('meta[name=sectok]').attr('content');
	//var home_url = $('meta[name=homeurl]').attr('content');

	// scroll to top
	$('#scroll_to_top button').click(function() {
	    $('html, body').animate({
	        scrollTop: 0
	    }, 700);
	});

	// show scroll to top button
	$(window).scroll(function () {
		if ($(window).scrollTop() > 0)
			$('#scroll_to_top > div').show('drop', { direction: 'down' }, function () {
				if ($(window).scrollTop() == 0) $(this).hide('drop', { direction: 'down' });
			});
		else
			$('#scroll_to_top > div').stop(true, true).hide('drop', { direction: 'down' });
	});

	$(window).resize(function () {
		adjust_desc_overlap();
	}).resize();

	// adjust consultation description to not overlap with consultation menu
	function adjust_desc_overlap (elem='body') {
		if (typeof elem == 'string') {
			elem = $(elem);
			if (elem.length <= 0)
				return;
		}
		elem.find('.kon-popis').each(function () {
			var one_kon = $(this);
			var kroom = one_kon.find('.kon_room');
			kroom.css('width', '');
			var clone = one_kon.parent().clone();
			clone.css('display', 'block').css('visibility','hidden');
			$('body').append(clone);
			var width1 = clone.find('.kon-menu').outerWidth();
			var width2 = clone.find('.kon_room').outerWidth();
			clone.remove();
			kroom.width(width2 - width1);
		});
	}

	// replaces variable placeholders in string with actual variables
	function repl_str (str, args=[]) {
		args.unshift(0);
		var re = /{\$(.+?)}/g;
		return str.replace(re, function (match, m1) {
			return args[m1];
		});
	}

	// init checkboxes style
	function reinit_checkboxes (area='body') {
		$(area + ' .checkbox-label .checkbox-replacement').remove();
		$(area + ' .checkbox-label input[type="checkbox"]').after('<span class="checkbox-replacement"></span>');
	}

	// remove stud_filter tags by click
	function remove_tag_on_click (elem) {
		if (typeof elem == 'string') {
			elem = $(elem);
			if (elem.length <= 0)
				return;
		}
		elem.find('.tagarea > div span').click(function () {
			$(this).parent().remove();
		});
	}

	// makes target textarea autoresizeable by writing
	function autoresize_textarea (elem, init_height=true, scrollAdjust=2) {
		if (typeof elem == 'string') {
			elem = $(elem);
			if (elem.length <= 0)
				return;
		}
		if (init_height)
			elem.css('height', elem[0].scrollHeight + scrollAdjust);
		elem.on('input', function () {
			this.style.height = "";
			this.style.height = Math.min(this.scrollHeight + scrollAdjust, 300) + "px";
		});
	}

	// helper function to get parent by selector
	function get_parent (elem, selec) {
		var parent = $(elem).parent();
		while (!parent.is(selec)) {
			if (parent.is('body'))
				return false;
			parent = parent.parent();
		}
		return parent;
	}

	// make .kon-rows selectable
	function selectable_kon_rows (just_one=null) {
		function select_row_onclick (event) {
			if ($(event.target).is('input[type=text]'))
				return;
			// first deselect rows of all other consultations
			var parent = get_parent(this, '.konzultace');
			var parentID = parent.prop('id');
			$('.konzultace.is_author:not(#' + parentID + ') .kon-row').removeClass('selected').find('.sel-checkbox input').prop('checked', false);
			$('.konzultace.is_author:not(#' + parentID + ') .kon-menu').find('.btn_disable, .btn_message, .btn_enable').hide();
			// then select clicked row
			if ($(this).toggleClass('selected').hasClass('selected'))
				$(this).find('.sel-checkbox input').prop('checked', true);
			else
				$(this).find('.sel-checkbox input').prop('checked', false);
			// show button - disable (if sections are not yet disabled)
			var btn = parent.find('.kon-menu .btn_disable');
			btn.stop(true, true);
			var btncol = btn.css('background-color');
			if (parent.find('.kon-row.selected:not(.disabled)').length > 0)
				btn.show().css('background-color', '#FFEE58').animate({ backgroundColor: btncol }, 200, null, function () { $(this).css('backgroundColor', ''); });
			else
				btn.hide();
			// show button - message (if signed user is selected)
			var btn = parent.find('.kon-menu .btn_message');
			btn.stop(true, true);
			if (parent.find('.kon-row.selected.user_present').length > 0)
				btn.show().css('background-color', '#FFEE58').animate({ backgroundColor: btncol }, 200, null, function () { $(this).css('backgroundColor', ''); });
			else
				btn.hide();
			// show button - enable (if disabled section is selected)
			var btn = parent.find('.kon-menu .btn_enable');
			btn.stop(true, true);
			if (parent.find('.kon-row.selected.disabled').length > 0)
				btn.show().css('background-color', '#FFEE58').animate({ backgroundColor: btncol }, 200, null, function () { $(this).css('backgroundColor', ''); });
			else
				btn.hide();
			// adjust kon_room size because additional menu buttons can be shown
			adjust_desc_overlap(parent);
		}

		if (just_one == null)
			// select kon rows
			$('.konzultace.is_author .kon-row:not(.is_past), .konzultace.is_author .kon-row.is_past.user_present').click(select_row_onclick);
		else
			just_one.click(select_row_onclick);
	}

	// insterst data into #main_container
	function insert_into_main (data) {
		$('#main_container').html(data);
		init_kon_display();
	}

	// sets or gets parameters in URL
	function url_param (param, value=null) {
		var current = [];
		// prepare current parameters
		var after_q_mark = window.location.href.split('?');
		if (after_q_mark.length > 1)
			after_q_mark[1].split('&').forEach(function (elem) {
				var part = elem.split('=');
				current[part[0]] = part[1];
			});

		if (value === null)
			return current[param];

		// change given parameters
		current[param] = value;
		var prep_url = '';
		for (var key in current) {
			if (current[key] != '' && current[key] != null)
				prep_url += key + '=' + current[key] + '&';
		}
		if (prep_url != '')
			prep_url = '?' + prep_url.slice(0, -1);
		window.location.replace(home_url + prep_url);
	}

	// leaves consultation editation area
	function kon_edit_hide (exception='') {
		if (exception != '')
			exception = ':not(#' + exception + ')';
		$('.konzultace.editing' + exception).removeClass('editing').each(function () {
			var elem = $(this);
			var popis_textarea = elem.find('.kon-popis .kon_descr textarea');
			if (popis_textarea.length > 0) {
				if (!popis_textarea.val())
					elem.find('.kon-popis .kon_descr').animate({ height: '-=' + popis_textarea.height() }, hide_show_row_anim_dur)
				elem.find('.kon-popis .kon_descr').html(popis_textarea.val());
				// kon_room
				var kroom = elem.find('.kon-popis .kon_room input');
				if (!kroom.val())
					elem.find('.kon-popis .kon_room').animate({ height: '-=' + kroom.height() }, hide_show_row_anim_dur)
				elem.find('.kon-popis .kon_room span').html(kroom.val());
			}
			// update stud. filters
			var stud_filter = '';
			elem.find('.kon_stud_filter .tagarea div[data-val]').each(function () {
				stud_filter += $(this).data('val') + ',';
			});
			if (stud_filter != '')
				stud_filter = stud_filter.slice(0, -1);
			$.post('ajax.php', {
				call: 'kon_update_stud_filter',
				target: elem.data('konid'),
				stud_filter: stud_filter,
				sec_tok: sec_tok
			}).done(function (data) {
				if (data)
					$('.main_error_container').html(data);
			});
			// hide section occupy notes
			elem.find('.kon-row .pozn input').each(function () {
				if ($(this).val() != '')
					get_parent(this, '.kon-row').addClass('occupied');
				else
					get_parent(this, '.kon-row').removeClass('occupied');
				$(this).parent().html($(this).val());
			});
		});
	}

	// hides user note editation
	function hide_user_edit_area (elem) {
		elem.html(elem.find('input').val());
		var but = elem.parent().find('.edit_pozn').removeClass('open_edit');
		but.unbind();
		edit_user_note_button(but);
	}

	// option for user to edit his note
	function edit_user_note_button (just_one=null) {
		if (just_one == null)
			just_one = $('.konzultace .kon-row.logged .edit_pozn');
		just_one.click(function () {
			$(this).unbind();
			// first hide all others
			$('.konzultace .kon-row .pozn').each(function () {
				if ($(this).find('input').length > 0) {
					hide_user_edit_area($(this));
				}
			});
			var this_pozn = $(this).parent().find('.pozn');
			this_pozn.html('<input type="text" placeholder="' + lang.script.studNotePlaceholder + '" value="' + this_pozn.html() + '">');
			this_pozn.find('input').focus();
			// hide on enter press
			this_pozn.find('input').on('keyup', function (event) {
				if (event.keyCode === 13)
					hide_user_edit_area(this_pozn);
			});
			// hide on checkmark click (also create checkmark)
			$(this).addClass('open_edit');
			$(this).click(function () {
				hide_user_edit_area(this_pozn);
			});
			// when writing note, dont send ajax after each key is pressed, instead wait a while
			var kon_input = this_pozn.find('input');
			var note_send_timeout = null;
			kon_input.keyup(function () {
				clearTimeout(note_send_timeout);
				note_send_timeout = setTimeout(function () {
					if (kon_input.length > 0)
						var note_to_send = kon_input.val();
					else
						var note_to_send = this_pozn.html();
					$.post('ajax.php', {
						call: 'user_edit_note',
						target: get_parent(this_pozn, '.konzultace').data('konid'),
						section: get_parent(this_pozn, '.kon-row').find('.cas').html(),
						note: note_to_send,
						sec_tok: sec_tok
					}).done(function (data) {
						if (data != 0)
							$('.main_error_container').html(data);
					});
				}, note_send_delay);
			});
		});
	}

	// gives consultation signin buttons functionality
	function kon_signin_buttons (just_one=null) {
		if (just_one == null)
			just_one = $('#main_container:not(.unlogged):not(.stud_not_show) .konzultace:not(.is_author) .kon-row:not(.disabled):not(.user_present):not(.is_past):not(.occupied)');
		just_one.click(function () {
			var this_row = $(this);
			$.post('ajax.php', {
				call: 'sign_to_kon',
				target: get_parent(this, '.konzultace').data('konid'),
				section: this_row.find('.cas').html(),
				sec_tok: sec_tok
			}).done(function (data) {
				if (data) {
					var given_elem = $(data).replaceAll(this_row);
					kon_signout_buttons(given_elem.find('.sign_out span'));
					edit_user_note_button(given_elem.find('.edit_pozn'));
				}
			});
		});
	}

	// gives consultation signout buttons functionality
	function kon_signout_buttons (just_one=null) {
		if (just_one == null)
			just_one = $('.konzultace .kon-row.user_present.logged .sign_out span');
		just_one.click(function () {
			var elem = this;
			$('#modal_confirm_dialog').modal('show');
			var kon_parent = get_parent(elem, '.konzultace');
			$('#modal_confirm_dialog .modal-message').html('<div class="kon-info">' + repl_str(lang.script.konMenuInfo, [kon_parent.data('konid'), kon_parent.find('.datum .datum-format').html(), kon_parent.find('.kon-row .cas').first().html()]) + '</div><div>' + lang.script.signoutPopup + '</div><textarea rows="2"></textarea>');
			$('#modal_confirm_dialog .choose-buttons').html(
				'<button class="btn-no" data-toggle="modal" data-target="#modal_confirm_dialog">' + lang.script.signoutNow + '</button>' +
				'<button class="btn-yes" data-toggle="modal" data-target="#modal_confirm_dialog">' + lang.script.signoutSend + '</button>' +
				'<button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog">' + lang.other.storn + '</button>'
				);
			$('#modal_confirm_dialog .choose-buttons button').click(function () {
				var pressed = $(this);
				if (pressed.hasClass('btn-no'))
					pressed = 'n';
				else if (pressed.hasClass('btn-yes'))
					pressed = 'y';
				else return;
				var this_row = get_parent(elem, '.kon-row');
				var msg = get_parent(this, '#modal_confirm_dialog').find('.modal-message textarea').val();
				$.post('ajax.php', {
					call: 'signout_from_kon',
					target: get_parent(this_row, '.konzultace').data('konid'),
					section: this_row.find('.cas').html(),
					message: msg,
					to_send: pressed,
					sec_tok: sec_tok
				}).done(function (data) {
					if (data)
						kon_signin_buttons($(data).replaceAll(this_row));
				});
			});
		});
	}

	// submits form for new consult creation
	function submit_create_new_kon (event) {
		// prepare filtered e-mail domains
		var stud_filter = '';
		$('#new_kon_form .kon_stud_filter .tagarea div[data-val]').each(function () {
			stud_filter += $(this).data('val') + ',';
		});
		if (stud_filter != '')
			stud_filter = stud_filter.slice(0, -1);
		var fields = {
			date: $('#new_kon_datepicker').val(),
			start: $('#time_sel_field').val(),
			sec_dur: $('#section_dur_field').val(),
			sec_num: $('#section_num_field').val(),
			room: $('#consult_room').val(),
			note: $('#kon_note_field').val(),
			checkboxes: '',
			stud_filter: stud_filter,
			sec_tok: sec_tok
		};
		$('#new_kon_form .notif_options_area input[type=checkbox]:checked').each(function () {
			fields.checkboxes += $(this).val() + ',';
		});
		fields.checkboxes = fields.checkboxes.slice(0, -1);
		fields.call = 'kon_create_new';
		// if delete original during duplication was selected
		if (event.data && event.data.del_orig)
			fields.del_orig = event.data.del_orig;
		// send to server
		$.post('ajax.php', fields).done(function (data) {
			if (data != 0)
				if (data.substr(0, 5) == 'Error') {
					data = data.substr(5).split('|');
					var toWrite = '';
					data.forEach(function (elem) {
						toWrite += '<div class="one_error">' + elem + '</div>';
					});
					$('#new_kon_form .error_area').html(toWrite);
				} else {
					insert_into_main(data);
					$('#new_kon_form .error_area').html('');
					$('#new_kon_modal').modal('hide');
				}
		});
	}

	// whilke eidting consultation make free sections occupyable by text
	function occupy_kon_sec (elem) {
		if (typeof elem == 'string') {
			elem = $(elem);
			if (elem.length <= 0)
				return;
		}
		elem.each(function () {
			var occupy_send_timeout = null;
			var konrow = $(this);
			konrow.find('.pozn').html('<input type="text" placeholder="' + lang.script.occupyPlaceholder + '" value="' + konrow.find('.pozn').html() + '">');
			var occupyInput = konrow.find('.pozn input');
			occupyInput.keyup(function () {
				clearTimeout(occupy_send_timeout);
				occupy_send_timeout = setTimeout(function () {
					if (occupyInput.length > 0)
						var note_to_send = occupyInput.val();
					else
						var note_to_send = konrow.find('.pozn').html();
					$.post('ajax.php', {
						call: 'kon_occupy_section',
						target: get_parent(konrow, '.konzultace').data('konid'),
						note: note_to_send,
						section: konrow.find('.cas').html(),
						sec_tok: sec_tok
					}).done(function (data) {
						if (data != 0)
							$('.main_error_container').html(data);
					});
				}, note_send_delay);
			});
		});
	}

	// appends jQuery functionality on elements when loading consultations
	function init_kon_display () {
		// kon menu buttons
		$('.konzultace .kon-menu button').click(function () {
			var kon_parent = get_parent(this, '.konzultace');
			var konID = kon_parent.data('konid');
			var kon_info = '<div class="kon-info">' + repl_str(lang.script.konMenuInfo, [konID, kon_parent.find('.datum .datum-format').html(), kon_parent.find('.kon-row .cas').first().html()]) + '</div>';

			// delete consultation button
			if ($(this).hasClass('btn_delete')) {
				$('#modal_confirm_dialog').modal('show');
				$('#modal_confirm_dialog .modal-message').html(kon_info + '<div>' + lang.script.konRlyDel + '</div><div class="message_receivers"></div>');
				$.post('ajax.php', {
					call: 'get_message_receivers',
					target: konID,
					sec_tok: sec_tok
				}).done(function (data) {
					if (data != 0)
						$('#modal_confirm_dialog .modal-message .message_receivers').html('<div>' + lang.script.konSendMsgSigned + '</div><div>' + data + '</div><textarea rows="2" class="del_msg"></textarea>');
					else
						$('#modal_confirm_dialog .modal-message .message_receivers').html('<input type="hidden" class="del_msg">');
					autoresize_textarea('#modal_confirm_dialog .modal-message .message_receivers textarea', false);
				});
				$('#modal_confirm_dialog .choose-buttons').html('<button class="btn-no">' + lang.script.delete + '</button><button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog">' + lang.other.storn + '</button>')
				$('#modal_confirm_dialog .choose-buttons .btn-no').click(function () {
					$.post('ajax.php', {
						call: 'kon_delete',
						target: konID,
						message: $('#modal_confirm_dialog .modal-message .del_msg').val(),
						sec_tok: sec_tok
					}).done(function (data) {
						$('#modal_confirm_dialog').modal('hide');
						insert_into_main(data);
					});
				});

			// duplicate consultation button
			} else if ($(this).hasClass('btn_duplicate')) {
				kon_edit_hide();
				// copy data	
				$('#new_kon_form #new_kon_datepicker').val(kon_parent.find('.datum .datum-format').html());
				$('#new_kon_form #time_sel_field').val(kon_parent.find('.kon-row .cas').first().html());
				$('#new_kon_form #section_num_field').val(kon_parent.find('.kon-row').length);
				$('#new_kon_form #kon_note_field').val(kon_parent.find('.kon-popis .kon_descr').html());
				$('#new_kon_form .kon_stud_filter .tagarea').html(kon_parent.find('.kon_stud_filter .tagarea').html());
				remove_tag_on_click('#new_kon_form .kon_stud_filter');
				var times0 = kon_parent.find('.kon-row .cas');
				var times1 = times0[0].innerText.split(':');
				var times2 = times0[1].innerText.split(':');
				times1 = Number(times1[0]*60) + Number(times1[1]); // to minutes
				times2 = Number(times2[0]*60) + Number(times2[1]); // to minutes
				times0 = times2 - times1;
				var timeres = times0 % 60;
				if (timeres < 10);
					timeres = '0' + timeres.toString();
				$('#new_kon_form #section_dur_field').val(Math.floor(times0 / 60) + ':' + timeres);
				// add fields for duplication purposes
				if (kon_parent.find('.kon-row.is_past').length < 1) {
					$('#new_kon_form .kon_create_button button:last-of-type').after('<button type="button" class="btn-no">' + lang.script.konDupliDelOrig + '</button>');
					$('#new_kon_form .kon_create_button button:last-of-type').click({ del_orig: konID }, submit_create_new_kon);
				}
				$('#new_kon_modal').modal('show');
				$('#new_kon_modal').on('hidden.bs.modal', function () {
					var del_orig = $('#new_kon_form .kon_create_button button');
					if (del_orig.length > 1)
						del_orig.last().remove();
				});
			
			// edit consultation button
			} else if ($(this).hasClass('btn_edit')) {
				// enbale/disable note editing
				var popis_textarea = kon_parent.find('.kon-popis .kon_descr textarea');
				if (popis_textarea.length > 0) {
					kon_edit_hide();
				} else {
					kon_edit_hide(kon_parent.prop('id'));
					kon_parent.addClass('editing');
					var note_send_timeout = null;
					var room_send_timeout = null;
					var kon_popis = kon_parent.find('.kon-popis .kon_descr');
					kon_popis.html('<textarea placeholder="' + lang.script.konNotePlaceholder + '" rows="1">' + kon_popis.html() + '</textarea>');
					var popis_textarea = kon_popis.find('textarea');
					autoresize_textarea(popis_textarea);
					if (!popis_textarea.val())
						kon_popis.height(0).animate({ height: '+=' + popis_textarea.height() }, hide_show_row_anim_dur, null, function () {
							$(this).css('height', '');
						});
					// kon_room edit
					var kroom = kon_parent.find('.kon_room span');
					kroom.html('<input type="text" value="' + kroom.html() + '">');
					var krooin = kroom.find('input');
					if (!krooin.val())
						kon_parent.find('.kon_room').height(0).animate({ height: '+=' + krooin.height() }, hide_show_row_anim_dur, null, function () {
							$(this).css('height', '');
						});
					// occupy notes edit
					occupy_kon_sec(kon_parent.find('.kon-row:not(.is_past):not(.user_present):not(.disabled)'));
					// when writing note, dont send ajax after each key is pressed, instead wait a while
					popis_textarea.keyup(function () {
						clearTimeout(note_send_timeout);
						note_send_timeout = setTimeout(function () {
							if (popis_textarea.length > 0)
								var note_to_send = popis_textarea.val();
							else
								var note_to_send = kon_popis.html();
							$.post('ajax.php', {
								call: 'kon_edit_note',
								target: konID,
								note: note_to_send,
								sec_tok: sec_tok
							}).done(function (data) {
								if (data != 0)
									$('.main_error_container').html(data);
							});
						}, note_send_delay);
					});
					krooin.keyup(function () {
						clearTimeout(room_send_timeout);
						room_send_timeout = setTimeout(function () {
							if (krooin.length > 0)
								var room_to_send = krooin.val();
							else
								var room_to_send = kroom.html();
							$.post('ajax.php', {
								call: 'kon_edit_room',
								target: konID,
								room: room_to_send,
								sec_tok: sec_tok
							}).done(function (data) {
								if (data != 0)
									$('.main_error_container').html(data);
							});
						}, note_send_delay);
					});
				}

			// sends message to selected participants
			} else if ($(this).hasClass('btn_message')) {
				$('#modal_confirm_dialog').modal('show');
				var toWrite = '';
				var recipients = [];
				var from_stud = 0;
				if (kon_parent.hasClass('is_author')) {
					toWrite = kon_info + '<div>' + lang.script.konSendMsgChosen + '</div><div class="message_receivers">';
					kon_parent.find('.kon-row.selected.user_present').each(function () {
						if ($.inArray($(this).data('login'), recipients) < 0) {
							recipients.push($(this).data('email'));
							toWrite += '&lt;' + $(this).data('email') + '&gt;, ';
						}
					});
					toWrite = toWrite.slice(0, -2);
				} else {
					from_stud = 1;
					toWrite = kon_info + '<div>' + lang.script.konSendMsgOne + '</div><div class="message_receivers">';
					$.post('ajax.php', {
						call: 'get_kantor_by_id',
						target: konID,
						sec_tok: sec_tok
					}).done(function (data) {
						if (data) {
							try {
								data = JSON.parse(data);
							} catch (e) {
								$('.main_error_container').html(data);
							}
							var recs = [{ 'email': data.email, 'name': (data.titles_before + ' ' + data.last_name + ' ' + data.first_name + ' ' + data.titles_after).trim() }];
							kon_parent.find('.kon-row.user_present:not(.logged)').each(function () {
								var elem = $(this);
								recs.push({ 'email': elem.data('email'), 'name': elem.find('.jmeno').html() });
							});
							var toWrite = '';
							for (var i = 0; i < recs.length; i++) {
								toWrite += '<label class="checkbox-label"><input type="checkbox" name="message_receivers[]" value="' + recs[i]['email'] + '">' + recs[i]['name'] + ' &#60;' + recs[i]['email'] + '&#62;</label>';
							}
							$('#modal_confirm_dialog .message_receivers').html(toWrite);
							$('#modal_confirm_dialog .message_receivers input[type="checkbox"]').first().prop('checked', true);
							reinit_checkboxes('#modal_confirm_dialog');
						}
					});
				}
				toWrite += '</div>';
				toWrite += '<textarea rows="2" class="send_msg"></textarea>';
				$('#modal_confirm_dialog .modal-message').html(toWrite);
				$('#modal_confirm_dialog .choose-buttons').html('<button class="btn-yes">' + lang.other.send + '</button><button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog">' + lang.other.storn + '</button>');
				autoresize_textarea('#modal_confirm_dialog .modal-message textarea', false);
				$('#modal_confirm_dialog .choose-buttons .btn-yes').click(function () {
					if (from_stud == 1)
						$('#modal_confirm_dialog .message_receivers input[name="message_receivers[]"]:checked').each(function () {
							recipients.push($(this).val());
						});
					$.post('ajax.php', {
						call: 'kon_send_message',
						recipients: recipients.toString(),
						target: konID,
						from_stud: from_stud,
						message: $('#modal_confirm_dialog .modal-message .send_msg').val(),
						sec_tok: sec_tok
					}).done(function (data) {
						if (data && data.substr(0, 2) == 'ok') {
							$('#modal_confirm_dialog').modal('hide');
						} else {
							$('#modal_confirm_dialog .choose-buttons').before(data);
						}
					});
				});

			// disable kon row or kick signed user
			} else if ($(this).hasClass('btn_disable')) {
				function send_ajax_to_disable (selected, disable_sec=1) {
					var sections = '';
					selected.find('.cas').each(function () {
						sections += $(this).html() + ',';
					});
					$.post('ajax.php', {
						call: 'kon_disable_row',
						target: konID,
						disable: disable_sec,
						sections: sections.slice(0, -1),
						sec_tok: sec_tok
					}).done(function (data) {
						$('#modal_confirm_dialog').modal('hide');
						if (data)
							insert_into_main(data);
					});
				}

				var selected = kon_parent.find('.kon-row.selected');
				if (selected.hasClass('user_present')) {
					$('#modal_confirm_dialog .modal-message').html(kon_info + lang.script.really);
					var toWrite = '';
					var user_present = false;
					var row_disabled = false;
					for (var i = 0; i < selected.length; i++) {
						if ($(selected[i]).hasClass('user_present'))
							user_present = true;
						if ($(selected[i]).hasClass('disabled'))
							row_disabled = true;
					}
					if (user_present)
						toWrite += '<button class="btn-no" data-disable="0">' + lang.script.konDelChosen + '</button>';
					toWrite += '<button class="btn-no" data-disable="1">' + lang.script.konDelDisChosen + '</button>';
					toWrite += '<button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog">' + lang.other.storn + '</button>';
					$('#modal_confirm_dialog .choose-buttons').html(toWrite);
					$('#modal_confirm_dialog').modal('show');
					$('#modal_confirm_dialog .choose-buttons button[data-disable]').click(function () {
						send_ajax_to_disable(selected, $(this).data('disable'));
					});
				} else if (selected.length > 0) {
					send_ajax_to_disable(selected);
				}

			// enable kon row
			} else if ($(this).hasClass('btn_enable')) {
				var sections = '';
				kon_parent.find('.kon-row.selected .cas').each(function () {
					sections += $(this).html() + ',';
				});
				$.post('ajax.php', {
					call: 'kon_disable_row',
					target: konID,
					disable: -1,
					sections: sections.slice(0, -1),
					sec_tok: sec_tok
				}).done(function (data) {
					if (data)
						insert_into_main(data);
				});
			
			// show notifications settings
			} else if ($(this).hasClass('btn_notifications')) {
				$('#modal_confirm_dialog .modal-message').html(kon_info + '<div><b>' + lang.script.konNotifDesc + '</b></div><div class="notif_options_area"></div>');
				$('#modal_confirm_dialog .choose-buttons').html('<button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog">' + lang.other.close + '</button>');
				$('#modal_confirm_dialog').modal('show');
				// check user notifications
				$.post('ajax.php', {
					call: 'kon_get_user_notifications',
					target: konID,
					sec_tok: sec_tok
				}).done(function (data) {
					$('#modal_confirm_dialog .notif_options_area').html(data);
					reinit_checkboxes('#modal_confirm_dialog');
					// update user notifications
					$('#modal_confirm_dialog .notif_options_area input[type=checkbox]').change(function () {
						var toWrite = '';
						get_parent(this, '.notif_options_area').find('input[type=checkbox]').each(function () {
							var elem = $(this);
							toWrite += elem.val() + ':';
							if (elem.is(':checked'))
								toWrite += '1,';
							else
								toWrite += '0,';
						});
						$.post('ajax.php', {
							call: 'kon_notifications_change',
							target: konID,
							fields: toWrite.slice(0, -1),
							sec_tok: sec_tok
						}).done(function (data) {
							if (data)
								$('.main_error_container').html(data);
						});
					});
				});
			
			// show consultation history
			} else if ($(this).hasClass('btn_history')) {
				$('#modal_confirm_dialog .modal-message').html(kon_info + '<div class="history_area"></div>');
				$('#modal_confirm_dialog .choose-buttons').html('<button class="btn-storn" data-toggle="modal" data-target="#modal_confirm_dialog">' + lang.other.close + '</button>');
				$('#modal_confirm_dialog').modal('show');
				$.post('ajax.php', {
					call: 'get_kon_history',
					target: konID,
					sec_tok: sec_tok
				}).done(function (data) {
					$('#modal_confirm_dialog .history_area').html(data);
				});
			}

			// finish conslutatation editations
			$('.konzultace .kon-edit-finish button').click(function () { kon_edit_hide(); });
		});

		// add row to constultation (top)
		$('.konzultace .kon-row-add.top-add button').click(function () {
			var konID = get_parent(this, '.konzultace').data('konid');
			$.post('ajax.php', {
				call: 'kon_add_section',
				target: konID,
				top: 1,
				sec_tok: sec_tok
			}).done(function (data) {
				if (data) {
					$('#kon_id_' + konID + ' .kon-row').first().before(data);
					selectable_kon_rows($('#kon_id_' + konID + ' .kon-row').first());
					occupy_kon_sec($('#kon_id_' + konID + ' .kon-row').first());
				}
			});
		});
		// add row to constultation (bot)
		$('.konzultace .kon-row-add.bot-add button').click(function () {
			var konID = get_parent(this, '.konzultace').data('konid');
			$.post('ajax.php', {
				call: 'kon_add_section',
				target: konID,
				top: 0,
				sec_tok: sec_tok
			}).done(function (data) {
				if (data) {
					$('#kon_id_' + konID + ' .kon-row').last().after(data);
					selectable_kon_rows($('#kon_id_' + konID + ' .kon-row').last());
					occupy_kon_sec($('#kon_id_' + konID + ' .kon-row').last());
				}
			});
		});

		// kantor selection
		if ($('#kantor_selection').length > 0)
			$.post('ajax.php', {
				call: 'get_kantor_list',
				sec_tok: sec_tok
			}).done(function (data) {
				if (data != 0) {
					try {
						data = JSON.parse(data);
					} catch (e) {
						$('.main_error_container').html(data);
					}
					var target_ac = $('#kantor_selection .kan_sel_input input');
					target_ac.autocomplete({
						source: data,
						minLength: 0,
						select: function (e, ui) {
							url_param('kantor', ui.item.value);
						}
					});
					if (target_ac.autocomplete('instance'))
						target_ac.autocomplete('instance')._renderItem = function( ul, item ) {
					    	return $('<li>').append('<div>' + item.label + '</div><div class="autocomp_kantor_login">&#60;' + item.login + '&#62;</div>').appendTo( ul );
					    };
					target_ac.click(function () {
						target_ac.autocomplete('search', '');
					});
				}
			});

		// kantor filter selection
		if ($('#kantor_selection').length > 0)
			$.post('ajax.php', {
				call: 'get_kantor_filter',
				sec_tok: sec_tok
			}).done(function (data) {
				if (data != 0) {
					try {
						data = JSON.parse(data);
					} catch (e) {
						$('.main_error_container').html(data);
					}
					var target_ac = $('#kantor_selection .kan_sel_filter input');
					target_ac.autocomplete({
						source: data,
						minLength: 0,
						select: function (e, ui) {
							if (ui.item.value != ' ')
								url_param('kfilter', ui.item.value);
							else
								url_param('kfilter', '');
						}
					});
					target_ac.click(function () {
						target_ac.autocomplete('search', '');
					});
				}
			});

		// date filter selection
		if ($('#daterange_selection').length > 0)
			$.post('ajax.php', {
				call: 'get_year_range_list',
				wanted: url_param('kantor'),
				sec_tok: sec_tok
			}).done(function (data) {
				if (data != 0) {
					try {
						data = JSON.parse(data);
					} catch (e) {
						$('.main_error_container').html(data);
					}
					var tf = ['from', 'to'];
					tf.forEach(function (elem) {
						var isset = (typeof url_param(elem) !== 'undefined');
						var fixed_dat = data.slice();
						for (var i = 0; i < fixed_dat.length; i++) {
							// remove last year from 'from'
							if (elem == 'from' && !isNaN(fixed_dat[i]['label'])) {
								fixed_dat.splice(i, 1);
								break;
							}
							// remove actual choice if already selected
							if (!isset && fixed_dat[i]['value'] == '') {
								fixed_dat.splice(i, 1);
								i--;
							}
						}
						var target_ac = $('#daterange_selection .time_' + elem + ' input');
						target_ac.autocomplete({
							source: fixed_dat,
							minLength: 0,
							select: function (e, ui) {
								url_param(elem, ui.item.value);
							}
						});
						target_ac.click(function () {
							target_ac.autocomplete('search', '');
						});
						var timeFromTimeout = null;
						target_ac.keyup(function (e) {
							if (e.which == 13) {
								clearTimeout(timeFromTimeout);
								url_param(elem, target_ac.val());
								return;
							}
							clearTimeout(timeFromTimeout);
							timeFromTimeout = setTimeout(function () {
								url_param(elem, target_ac.val());
							}, 2000);
						});
					});
				}
			});

		// student filter selection
		if ($('.kon_stud_filter').length > 0)
			$.post('ajax.php', {
				call: 'get_kantor_filter',
				for_stud: 1,
				sec_tok: sec_tok
			}).done(function (data) {
				if (data != 0) {
					try {
						data = JSON.parse(data);
					} catch (e) {
						$('.main_error_container').html(data);
					}
					$('.kon_stud_filter input:not(.ui-autocomplete-input)').each(function () {
						var elem = $(this);
						var par = elem.parent();
						elem.autocomplete({
							source: data,
							minLength: 0,
							select: function (e, ui) {
								if (par.find('.tagarea div[data-val="' + ui.item.value + '"]').length < 1) {
									par.find('.tagarea').append('<div data-val="' + ui.item.value + '">' + ui.item.label + '<span>x</span></div>');
									remove_tag_on_click(par);
								}
								$(this).val('');
								return false;
							}
						});
						elem.click(function () {
							elem.autocomplete('search', '');
						});
					});
				}
			});

		kon_signin_buttons();
		kon_signout_buttons();
		edit_user_note_button();
		reinit_checkboxes();
		selectable_kon_rows();
		remove_tag_on_click('.kon_stud_filter');
	}

	init_kon_display();
	autoresize_textarea($('#new_kon_form #kon_note_field'), false);

	// empty errors when opening new_kon modal
	$('[data-toggle="modal"][data-target="#new_kon_modal"]').click(function () {
		$('#new_kon_modal .modal-content .error_area').empty();
	});

	// adjust daterange input padding
	$('#daterange_selection input').each(function () {
		var elem = $(this);
		var prebel = elem.parent().find('span');
		elem.css('padding-left', prebel.width() + parseInt(prebel.css('margin-left')));
	});

	/* * * * * * ** * * * * */
	/* * New kon creation * */
	/* * * * * * ** * * * * */

	// create new consultation
	$('#new_kon_form .kon_create_button button').click(submit_create_new_kon);

	// print notifications choice
	if ($('#new_kon_form').length > 0)
		$.post('ajax.php', {
			call: 'kon_just_notif_fields',
			sec_tok: sec_tok
		}).done(function (data) {
			$('#new_kon_form .kon_notif_choice .notif_options_area').html(data);
			reinit_checkboxes('#new_kon_form .kon_notif_choice .notif_options_area');
		});

	// calculate consultation end
	$('#time_sel_field, #section_dur_field, #section_num_field').on('keyup totalcalc', function () {
		var start = $('#time_sel_field').val().split(':');
		if (start.length <= 1)
			return;
		start = Number(start[0]) * 60 + Number(start[1]);

		var sec_dur = $('#section_dur_field').val().split(':');
		if (sec_dur.length <= 1)
			sec_dur = [0, sec_dur[0]];
		sec_dur = Number(sec_dur[0]) * 60 + Number(sec_dur[1]);

		var calced = start + sec_dur * Number($('#section_num_field').val());
		if (isNaN(calced))
			return;
		else if (calced >= start + 24 * 60) {
			$('#kon_end_calculated').html('-');
			return;
		} else if (calced >= 24 * 60)
			calced -= 24 * 60;

		calced = [Math.floor(calced / 60).toString(), (calced % 60).toString()];
		if (calced[1].length <= 1)
			calced[1] = '0' + calced[1];
		$('#kon_end_calculated').html(calced[0] + ':' + calced[1]);
	});
	$('#time_sel_field').trigger('totalcalc');

	// value assist controls
	$('.value_assist').each(function () {
		var elem = $(this);
		var step = elem.data('step');
		var type = elem.data('type');
		var ifmin = elem.data('min');
		var ifmax = elem.data('max');
		var mouseHoldInterval = null;
		if (!step)
			step = 1;

		function calculateNewValues (thiselem) {
			if (type == 'time') {
				var val = elem.val().split(':');
				if (val.length <= 1)
					val = [0, val[0]];
				val = Number(val[0]) * 60 + Number(val[1]);

				if (thiselem.hasClass('va_left'))
					val -= step;
				else if (thiselem.hasClass('va_right'))
					val += step;

				if (isNaN(val))
					return;
				else if (val >= 24 * 60)
					val = 0;
				else if (val < 0)
					val += 24 * 60;

				val = [Math.floor(val / 60).toString(), (val % 60).toString()];
				if (val[1].length <= 1)
					val[1] = '0' + val[1];
				elem.val(val[0] + ':' + val[1]);
			} else {
				var val = Number(elem.val());
				if (thiselem.hasClass('va_left'))
					val -= step;
				else if (thiselem.hasClass('va_right'))
					val += step;

				if (isNaN(val))
					return;
				if (ifmin && val < ifmin)
					val = ifmin;
				if (ifmax && val > ifmax)
					val = ifmax;
				elem.val(val);
			}
			elem.trigger('totalcalc');
		}
		elem.before('<button type="button" class="va_control va_left just_created"><i class="glyphicon glyphicon-minus"></i></button>');
		elem.after('<button type="button" class="va_control va_right just_created"><i class="glyphicon glyphicon-plus"></i></button>');
		elem.parent().find('.va_control.just_created').removeClass('just_created').mousedown(function () {
			var thiselem = $(this);
			calculateNewValues(thiselem);
			mouseHoldInterval = setTimeout(function () {
				mouseHoldInterval = setInterval(function () { calculateNewValues(thiselem); }, 300);
			}, 500);
		}).on('mouseup mouseout', function () {
			clearInterval(mouseHoldInterval);
		});
	});

	$('.cbutton-effect').click(function () {
		$(this).addClass('cbutton-click');
	});

	/* * * * * * * * * * * */
	/* * Login/Reg forms * */
	/* * * * * * * * * * * */

	// back button
	$('.rfr_by_js').click(function () {
		window.location.href = window.location.href;
	});

	// set focus on login field
	$('#login-form input[name=email]').focus();

	// switch between login and register forms
	$('#login-form .switch-forms, #register-form .switch-forms').click(function () {
		$('#login-form, #register-form').toggleClass('switched-to-bg');
		var form = get_parent(this, '#login-form, #register-form');
		var selec = ['#login-form', '#register-form'];
		if (form.is(selec[0]))
			selec = [selec[1], selec[0]];
		$(selec[0] + ' input[name=email]').val($(selec[1] + ' input[name=email]').val());
	});

	// show room input when user wants to be a kantor
	$('#register-form .iskantor-class label.checkbox-label').click(function () {
		if ($(this).find('input[type=checkbox]').is(':checked'))
			$('#register-form .room-class').addClass('visible');
		else
			$('#register-form .room-class').removeClass('visible');
	});

	// show titles inputs when user wants to add titles
	$('#register-form .addtitles-class label.checkbox-label').click(function () {
		if ($(this).find('input[type=checkbox]').is(':checked'))
			$('#register-form .titles').addClass('visible');
		else
			$('#register-form .titles').removeClass('visible');
	});

	// hide errors/messages
	$('.errors, .top_messages').click(function () {
		$(this).slideUp();
	});

	// check allowed values of register/pass-reset form fields
	var regform = $('#register-form, #pass-reset-form, #profile_edit, #modal_new_pass');
	if (regform.length > 0) {
		var checkDelay = 500;

		// login e-mail
		var inputTimeout1 = null;
		regform.find('input[name="email"]').on('input', function () {
			var elem = $(this);
			var for_reset = 0;
			elem.removeClass('wrong_val');
			clearTimeout(inputTimeout1);
			if (get_parent(elem, '#pass-reset-form'))
				for_reset = 1;
			inputTimeout1 = setTimeout(function () {
				$.post('ajax.php', {
					call: 'check_input_format',
					email: elem.val(),
					for_reset: for_reset,
					sec_tok: sec_tok
				}).done(function (data) {
					if (data) {
						elem.addClass('wrong_val');
						elem.next('.input-error').html(data);
					}
				});
			}, checkDelay);
		});

		// password
		var inputTimeout2 = null;
		var pass1 = regform.find('input[name="pass1"]');
		var pass2 = regform.find('input[name="pass2"]');
		pass1.add(pass2).on('input', function () {
			var elem = $(this);
			pass1.add(pass2).removeClass('wrong_val');
			clearTimeout(inputTimeout2);
			inputTimeout2 = setTimeout(function () {
				$.post('ajax.php', {
					call: 'check_input_format',
					pass1: pass1.val(),
					pass2: pass2.val(),
					sec_tok: sec_tok
				}).done(function (data) {
					if (data) {
						data = data.split('|');
						var wanted = null;
						if (data[0] == 'pass2')
							wanted = pass2;
						else if (data[1] == 'pass2')
							wanted = pass1.add(pass2);
						else
							wanted = pass1;
						wanted.addClass('wrong_val');
						wanted.next('.input-error').html(data[data.length - 1]);
					}
				});
			}, checkDelay);
		});

		// firstname, lastname
		var nameouts = [null, null];
		var nameputs = ['input[name="firstname"]', 'input[name="lastname"]'];
		for (var i = 0; i < nameputs.length; i++) {
			regform.find(nameputs[i]).on('input', function () {
				var elem = $(this);
				elem.removeClass('wrong_val');
				clearTimeout(nameouts[i]);
				nameouts[i] = setTimeout(function () {
					$.post('ajax.php', {
						call: 'check_input_format',
						name: elem.val(),
						sec_tok: sec_tok
					}).done(function (data) {
						if (data) {
							elem.addClass('wrong_val');
							elem.next('.input-error').html(data);
						}
					});
				}, checkDelay);
			});
		}

		// titles
		var titleouts = [null, null];
		var titleputs = ['input[name="titles_before"]', 'input[name="titles_after"]'];
		for (var i = 0; i < titleputs.length; i++) {
			regform.find(titleputs[i]).on('input', function () {
				var elem = $(this);
				elem.add(get_parent(elem, '.titles')).removeClass('wrong_val');
				clearTimeout(titleouts[i]);
				titleouts[i] = setTimeout(function () {
					$.post('ajax.php', {
						call: 'check_input_format',
						titles: elem.val(),
						sec_tok: sec_tok
					}).done(function (data) {
						if (data) {
							elem.add(get_parent(elem, '.titles')).addClass('wrong_val');
							elem.next('.input-error').html(data);
						}
					});
				}, checkDelay);
			});
		}
	}

	/* * * * * * * */
	/* * Profile * */
	/* * * * * * * */

	// upgrade/degrade user account
	$('#modal_confirm_dialog .change_level').click(function () {
		$.post('ajax.php', {
			call: 'change_acc_level',
			changeto: $(this).data('changeto'),
			sec_tok: sec_tok
		}).done(function (data) {
			if (data)
				$('#modal_confirm_dialog .modal-message').html(data);
			else
				window.location.reload(true);
		});
	});

	// change password
	$('#modal_new_pass .submit_new_pass').click(function () {
		var form = $('#modal_new_pass form');
		$.post('ajax.php', {
			call: 'submit_new_pass',
			pass_orig: form.find('input[name="pass_orig"]').val(),
			pass1: form.find('input[name="pass1"]').val(),
			pass2: form.find('input[name="pass2"]').val(),
			sec_tok: sec_tok
		}).done(function (data) {
			if (data)
				form.find('.error_pass_area').html(data);
			else
				window.location.reload(true);
		});
	});

	// prevent enter on new pass form
	$('#modal_new_pass').on('keyup keypress', function(e) {
		var keyCode = e.keyCode || e.which;
		if (keyCode === 13) { 
			e.preventDefault();
			return false;
		}
	});

	/* * * *  * * * */
	/* * Language * */
	/* * * *  * * * */

	$('#lang_sel a').click(function () {
		$.post('ajax.php', {
			call: 'change_language',
			lang: $(this).attr('href'),
			sec_tok: sec_tok
		}).done(function (data) {
			if (data)
				console.log(data);
			else
				window.location.reload(true);
		});
		return false;
	});

	/* * * * ** * * */
	/* * Feedback * */
	/* * * * ** * * */

	$('#modal_feedback .feedback_submit').click(function () {
		$.post('ajax.php', {
			call: 'save_feedback',
			text: $('#feedback_textarea').val(),
			sec_tok: sec_tok
		}).done(function (data) {
			$('#feedback_textarea').val('');
			$('#modal_feedback').modal('hide');
		});
	});

	$('.feedback_note .feed_close').click(function () {
		$.post('ajax.php', {
			call: 'stop_showing_feedback',
			sec_tok: sec_tok
		}).done(function (data) {
			$('.feedback_note').hide();
		});
	});
});