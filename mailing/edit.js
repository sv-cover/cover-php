var session_id = document.location.href.match(/session=([a-z0-9]+)/)[1];

$('.mailing-editable').each(function() {
	var section = this;

	var edit = function(e) {
		e.preventDefault();

		$.get('index.php?session=' + session_id + '&section=' + section.id + '&mode=controls', function(html) {
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
			$('<a href="#">edit</a>')
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

	var nav = $('<ul class="mailing-edit-nav">');

	nav.append('<li>Preview: <a href="index.php?session=' + session_id + '" target="_blank">HTML</a> or <a href="index.php?session=' + session_id + '&amp;mode=text" target="_blank">Text</a></li>');

	nav.append($('<li>').append($('<a href="#">Save</a>').click(save)));

	nav.append($('<li>').append($('<a href="#">Load</a>').click(load)));

	nav.append($('<li>').append($('<a href="#">Reset</a>').click(reset)));

	$(document.body).prepend(nav);
});