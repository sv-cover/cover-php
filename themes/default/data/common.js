function clear_text(input, def) {
	if (def == '' || input.value == def)
		input.value = '';
	else {
		input.selectionStart = 0;
		input.selectionEnd = input.value.length;
	}
}

function append_ai_email(input) {
	if (input.value.indexOf('@') < 0)
		input.value += '@ai.rug.nl';
}

function alert_request() {
	alert(this.get_response());
}

function add_request(str, add) {
	var test = str.replace(/[^\?]+(\?$|\?.+)/, "$1")

	if (test == str)
		return str + '?' + add;
	
	if (test.length == 1)
		return str + add;
	else
		return str + '&' + add;
}

function submit_form(name, xmlrequest) {
	if (!xmlrequest) {
		document.forms[name].submit();
		return;
	}
	
	var request = new Connection();
	request.on_task_finished = alert_request;

	var action = document.forms[name].getAttribute('action');
	
	action = add_request(action, 'xmlrequest');
	request.post(name, action);
}

function reset_form(name) {
	document.forms[name].reset();
}

jQuery(function($) {
	$('.hide-by-default').hide();

	$(document).on('click', "a[href^='#']", function(e) {
		$(this.href.match(/(#.+)$/)[1]).show();
	});

	$('.dropdown-button').each(function() {
		var $button = $(this);

		$(this).find('.button').click(function() {
			$button.toggleClass('open');
		});

		$(document).click(function(e) {
			if ($(e.target).closest('.dropdown-button').get(0) != $button.get(0))
				$button.removeClass('open');
		});
	})
});

// Inline links (use data-placement-selector and data-partial-selector attributes)
jQuery(function($) {
	var inline_link_handler = function(e) {
		e.preventDefault();

		var $target = null;

		if ($(this).data('placement-selector') == 'modal') {
			var $modal = $('<div class="modal">'),
				$modalWindow = $('<div class="window">').appendTo($modal);

			$target = $('<div>').appendTo($modalWindow);

			$modal.insertBefore($('.world'));//.delay(100).addClass('')

			// Close modal on submit
			$modal.on('submit', 'form', function(e) {
				e.preventDefault();
				$modal.remove();
				$.post($(this).attr('action'), $(this).serializeArray(), function(text) {
					document.location.reload();
				});
			});
		}
		else {
			$target = $(this).closest($(this).data('placement-selector'));
		}

		var url = this.nodeName == 'FORM'
			? this.action
			: this.href;

		if ($(this).data('partial-selector'))
			url += ' ' + $(this).data('partial-selector');

		$target.css({'opacity': 0.5});

		$tmp = $(document.createDocumentFragment());

		if ($(this).attr('method') == 'post') {
			$tmp.load(url, $(this).serializeArray(), function(text, status, xhr) {
				$target.replaceWith($tmp);
			});
		}
		else
			$tmp.load(url, function(text, status, xhr) {
				$target.replaceWith($tmp);
			});
	};

	$(document)
		.on('click', 'a[data-placement-selector]', inline_link_handler)
		.on('submit', 'form[data-placement-selector]', inline_link_handler);
});

jQuery.fn.autocompleteAlmanac = function(options)
{
	var defaults = {
		minLength: 3,
        source: function(request, response) {
			$.getJSON('almanak.php', {
				'search': request.term
			}, response);
		},
		focus: function() {
			return false;
		}
	};

	jQuery.extend(defaults, options || {});

	return $(this).autocomplete(defaults).each(function() {
		$(this).data('ui-autocomplete')._renderItem = function(ul, item) {
			return $('<li>').append(
				$('<a class="profile">')
					.append($('<img class="picture">').attr('src', 'foto.php?lid_id=' + item.id + '&get_thumb=thumb'))
					.append($('<span class="name">').text(item.name))
					.append($('<span class="starting-year">').text(item.starting_year))
			).appendTo(ul);
		};
	});
};

jQuery(function($) {
	$('form.privacy-preference').submit(function(e) {
		e.preventDefault();

		$.post(this.action, $(this).serializeArray(), function(response, status, xhr) {
			$('#photo_' + response.photo_id)
				.removeClass('privacy-visible privacy-hidden')
				.addClass('privacy-' + response.visibility);
		});
	});

	$('form.privacy-preference').focus(function(e) {
		var pos = $(this).offset();
		console.log(pos);

		$(this).children('ul').show().css({
			'position': 'fixed',
			'top': pos.top - window.scrollY + 'px',
			'left': pos.left - window.scrollX + 'px'
		});
	});

	$('form.privacy-preference').blur(function(e) {
		$(this).children('ul').hide();
	});

	$('form.privacy-preference').children('ul').hide();

	$('form.privacy-preference input[type=radio]').change(function(e) {
		$(this.form).submit();
	});
});
