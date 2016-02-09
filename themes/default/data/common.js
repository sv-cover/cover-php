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
		// Do not disturb any effect of modifier keys
		if (e.ctrlKey || e.shiftKey || e.metaKey)
			return;

		e.preventDefault();

		var $target = null;

		if ($(this).data('placement-selector') == 'modal') {
			var $modal = $('<div class="modal">'),
				$modalWindow = $('<div class="window">').appendTo($modal),
				$closeButton = $('<button class="close-button">&times;</button>').appendTo($modalWindow);

			$target = $('<div>').appendTo($modalWindow);
			$target.text('Loading…');

			$modal.insertBefore($('.world'));//.delay(100).addClass('')

			// Close the modal on clicking the close button
			$closeButton.on('click', function(e) {
				e.preventDefault();
				$modal.remove();
			});

			// Close the model on pressing the escape key while inside
			$modal.on('keydown', function(e) {
				if (e.keyCode == 27) {
					e.preventDefault();
					$modal.remove();
				}
			});
		}
		else {
			$target = $(this).closest($(this).data('placement-selector'));
		}

		var url = this.nodeName == 'FORM'
			? $(this).attr('action')
			: this.href;

		var selector = $(this).data('partial-selector') || 'body';
		
		$target.css({'opacity': 0.5});

		var tmp = document.createDocumentFragment();

		var addPartialToTarget = function(text, status, xhr) {
			var partial = tmp.querySelector(selector);
			$target.replaceWith(partial);
			$(document.body).trigger(jQuery.Event('partial-content-loaded', {target: partial}));
		};

		//console.log(url);

		if ($(this).attr('method') == 'post') {
			$(tmp).load(url, $(this).serializeArray(), addPartialToTarget);
		}
		else
			$(tmp).load(url, addPartialToTarget);
	};

	$(document)
		.on('click', 'a[data-placement-selector]', inline_link_handler)
		.on('submit', 'form[data-placement-selector]', inline_link_handler);
});

jQuery(function($) {
	$(document).on('click', 'a[data-image-popup]', function(e) {
		if ($(this).data('image-popup') !== 'modal')
			return;

		// Do not disturb any effect of modifier keys
		if (e.ctrlKey || e.shiftKey || e.metaKey)
			return;

		e.preventDefault();

		var $modal = $('<div class="modal">').prop('title', 'Click anywhere to close'),
			$modalWindow = $('<div class="window">').appendTo($modal),
			$closeButton = $('<button class="close-button">&times;</button>').appendTo($modalWindow),
			$image = $('<img>').css('max-width', '100%').appendTo($modalWindow);

		$modal.insertBefore($('.world'));

		$modal.on('click', function(e) {
			e.preventDefault();
			$modal.remove();
		});

		$image.prop('src', $(this).prop('href'));
	});
});

jQuery.fn.autocompleteAlmanac = function(options)
{
	var defaults = {
		minLength: 3,
        source: function(request, response) {
			$.getJSON('almanak.php', {
				'search': request.term,
				'limit': 15
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
					.append($('<img class="picture">').attr('src', 'foto.php?lid_id=' + item.id + '&format=square&width=60'))
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

$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('fieldset:not(.jquery-fieldset)').each(function(i, fieldset) {
		$(fieldset).addClass('jquery-fieldset');

		var masterSwitch = $(fieldset).find('legend input[type=checkbox]');

		var toggles = $(fieldset).find('input').not(masterSwitch);

		var update = function() {
			toggles.prop('disabled', !masterSwitch.is(':checked'));
		};

		update();

		masterSwitch.on('change', update);
	});

	$(e.target).find('input[data-autocomplete=member_id]').each(function(i, field) {
		$(field).autocompleteAlmanac({
			select: function(event, ui) {
				$(this).val(ui.item.id);
				return false;
			}
		});
	});
});

// Let's track clicks :D
$(document).on('ready', function(e) {
	// BookCee webshop
	$(e.target).find('.boekcie-webshop-call-to-action a').click(function(e) {
		ga('send', 'event', 'button', 'click', 'buy-books-button');
	});

	// Banners at the right side of the page
	$(e.target).find('.aff.column a').click(function(e) {
		ga('send', 'event', 'button', 'click', 'banner', $(this).attr('href'));
	});
});