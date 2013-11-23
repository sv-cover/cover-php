var session_id = document.location.href.match(/session=([a-z0-9]+)/)[1];

$('.mailing-editable').each(function() {
	var section = this;

	var edit = function(e) {
		e.preventDefault();

		$.get(this.href, function(html) {
			$(section).html(html);

			$(section).find('form').submit(submit);
		});
	};

	var submit = function(e)
	{
		$.post(this.action, $(this).serialize(), function(html) {
			$(section).html(html);
			addEditLink();
		});

		e.preventDefault();
	};

	var addEditLink = function()
	{
		$(section).find('h2').first().append(
			$('<a>edit</a>')
				.attr('href', 'index.php?session=' + session_id + '&section=' + section.id + '&mode=controls')
				.click(edit)
				.css({
					'font-size': '12px',
					'float': 'right',
					'display': 'inline-block'
				}));
	};

	addEditLink();
});

$(function() {
	var feedback = function(response) {
		alert(response);
	};

	var reload = function(response) {
		alert(response);
		document.location.reload();
	};

	var save = function(e) {
		e.preventDefault();

		var filename = prompt('filename');

		if (!filename)
			return;

		$.post(document.location.href, {
			'action': 'save',
			'name': filename
		}, feedback);
	};

	var load = function(e) {
		e.preventDefault();

		var filename = prompt('filename');

		if (!filename)
			return;

		$.post(document.location.href, {
			'action': 'load',
			'name': filename
		}, reload);
	};

	var reset = function(e) {
		e.preventDefault();

		if (!confirm('Do you want to reset the newsletter to the default content?'))
			return;

		$.post(document.location.href, {
			'action': 'reset'
		}, reload);
	};

	var changeDate = function(dateText, datepicker) {
		$(this).datepicker('hide');

		$.post(document.location.href, {
			'action': 'set-date',
			'date': dateText
		}, reload);
	};

	var openDatePicker = function(e) {
		e.preventDefault();

		var x = $(this).position().left,
			y = $(this).position().top + $(this).height();

		$(this).datepicker(
			'dialog',
			$('body').data('date'),
			changeDate,
			{'dateFormat': 'yy-mm-dd'},
			[x, y]);
	};

	var submit = function(e) {
		e.preventDefault();

		var content = $('<div/>').html('\
			<form>\
				<label for="email">Email address</label>\
				<input type="text" name="email" id="email" placeholder="mailing@svcover.nl">\
			</form>');

		content.dialog({
			modal: true,
			buttons: {
				'Submit': function() {
					$(this).dialog('close');
					$.post(document.location.href, {
						action: 'submit',
						email: content.find('#email').val()
					}, feedback);
				},
				'Cancel': function() {
					$(this).dialog('close');
				}
			}
		});
	}

	var nav = $('<ul class="mailing-edit-nav">');

	nav.append('<li>Preview: <a href="index.php?session=' + session_id + '" target="_blank">HTML</a> or <a href="index.php?session=' + session_id + '&amp;mode=text" target="_blank">Text</a></li>');

	nav.append($('<li>').append($('<a href="#">Save</a>').click(save)));

	nav.append($('<li>').append($('<a href="#">Load</a>').click(load)));

	nav.append($('<li>').append($('<a href="#">Reset</a>').click(reset)));

	nav.append($('<li>').append($('<a href="#">Date of newsletter: ' + $('body').data('date') + '</a>').click(openDatePicker)));

	nav.append($('<li>').append($('<a href="#">Submit &amp; Archive</a>').click(submit)));

	$(document.body).prepend(nav);
});