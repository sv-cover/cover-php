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

			// TODO: append at the bottom of body, which is way quicker.
			// Currently the photo viewer does it that way, but you'll
			// also need to add a modal-open class to body, and you'll
			// need to keep track of how many popups are stacked before
			// removing that class again.
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

/* Extend jQuery's detach() function to trigger events */
(function() {
	var _detach = jQuery.fn.detach;

	jQuery.fn.detach = function(selector) {
		var elements = $(this);
		
		if (selector)
			elements = elements.filter(selector);

		elements.each(function() {
			$(this).trigger(jQuery.Event('detach', {target: this}));
		});

		return _detach.call(this, selector);
	};
})();

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

// Show previews for images when available
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('img[data-preview]').each(function() {
		var image = $(this);

		var original = new Image();
		original.onload = function() {
			image.prop('src', original.src);
			image.removeClass('loading');
		};

		original.src = image.prop('src');

		// Show preload image
		image.prop('src', image.data('preview'));
		image.addClass('loading');
	});
});

/* Face tagging in photos */
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('#foto').each(function() {
		var $foto = $(this), // The container with all the buttons and comments etc.
			$photo = $foto.find('#photo'), // The photo image including faces
			$image = $photo.find('.full-size-photo'), // Just the figure el with the img tag.
			$toggle = $foto.find('#tagging-toggle'),
			cancelNextClick = false;

		$foto.find('.like-form').submit(function(e) {
			e.preventDefault();
			$form = $(this);
			$.post($form.attr('action'), $form.serializeArray(), function(response) {
				$form.closest('#foto').toggleClass('liked', response.liked);
				$form.find('.like-button').attr('title', $form.find('.like-button').data('title')[response.liked ? 0 : 1]);
				$form.find('.like-count').text(response.likes);
			});
		});

		function human_implode(elements, glue) {
			if (elements.length < 2)
				return elements.join('');

			return elements.slice(0, -1).join(', ') + ' ' + glue + ' ' + elements.slice(-1).join('');
		};

		function update_names_list() {
			$foto.find('.photo-meta .photo-faces').each(function() {
				var $area = $(this)
				var $toggle = $area.find('.tag-faces-toggle').detach();
				var format = $area.data('template-format');
				var faces = [], unknown = 0;

				$photo.find('.face .tag-label .name').each(function() {
					if ($(this).attr('href') == '#' || !$(this).attr('href'))
						++unknown;
					else {
						var $face = $(this).clone();
						$face.removeAttr('style');
						if ($face.closest('.face').prop('id')) // todo: fix this
							$face.attr('data-face-id', $face.closest('.face').prop('id').replace(/^face_/, ''));
						faces.push($face.prop('outerHTML'));
					}
				});

				if (unknown > 0)
					faces.push(format[unknown === 1 ? 'unknown_sg' : 'unknown_pl'].replace('%d', unknown));

				// Replace the HTML with the new names
				$area.html(format.intro.replace('%s', human_implode(faces, format.and)));

				// and append the toggle again.
				$area.append($toggle);
			});
		};

		function make_face_editable($face)
		{
			var accept = function(lid_id, name) {
				$face.removeClass('untagged');

				$face.find('.tag-label .name')
					.text(name)
					.attr('href', lid_id !== null ? 'profiel.php?lid=' + lid_id : '#');

				var data = lid_id !== null
					? [{name: 'lid_id', value: lid_id}]
					: [{name: 'custom_label', value: name}];

				$.post($face.data('update-action'), data, function() {}, 'json');

				// Update names list in the popup
				update_names_list();
			};

			$face.resizable({
				containment: $photo,
				aspectRatio: 1,
				stop: function(event, ui) {
					var data = [
						{name: 'w', value: ui.size.width / $image.width()},
						{name: 'h', value: ui.size.height / $image.height()}
					];

					$.post($face.data('update-action'), data, function() {}, 'json');
				}
			});
			
			$face.draggable({
				containment: $photo,
				stop: function(event, ui) {
					var data = [
						{name: 'x', value: ui.position.left / $image.width()},
						{name: 'y', value: ui.position.top / $image.height()}
					];
					
					$.post($face.data('update-action'), data, function() {}, 'json');
				}
			});

			$('<button class="delete-button">&times;</button>')
				.click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					$.post($face.data('delete-action'), [], function() {}, 'json');
					$face.remove();
					update_names_list();
				})
				.appendTo($face);

			$face.click(function(e) {
				e.preventDefault();
				e.stopPropagation();
				update_names_list();
				$face.find('.name').hide();
				$face.find('.tag-search').show().focus();
			});

			$('<input type="text" class="tag-search" spellcheck="false">')
				.on('keydown', function(e) {
					switch (e.keyCode) {
						case $.ui.keyCode.TAB:
							if ($(this).data('ui-autocomplete').menu.active)
								e.preventDefault();
							break;

						case $.ui.keyCode.ENTER:
							if ($(this).data('ui-autocomplete').menu.active)
								break;
							accept(null, $(this).val());
							// fall-through intentional
						
						case $.ui.keyCode.ESCAPE:
							$(this).blur();
							e.preventDefault();
							cancelNextClick = false;
							break;
					}
				})
				.on('blur', function(e) {
					$(this).val('').hide();
					$face.find('.name').show();
					cancelNextClick = true;
				})
				.autocomplete({
					source: function(request, response) {
						$.getJSON('almanak.php', {
							'search': request.term
						}, response);
					},
					search: function() {
						if (this.value.length < 2)
							return false;
					},
					focus: function() {
						return false;
					},
					select: function(event, ui) {
						accept(ui.item.id, ui.item.name);
						$(this).blur();
						cancelNextClick = false;
						return false;
					}
				})
				.each(function() {
					$(this).data('ui-autocomplete')._renderItem = function(ul, item) {
						return $('<li>').append(
							$('<a class="profile">')
								.append($('<img class="picture">').attr('src', 'foto.php?lid_id=' + item.id + '&format=square&width=60'))
								.append($('<span class="name">').text(item.name))
								.append($('<span class="starting-year">').text(item.starting_year))
						).appendTo(ul);
					};
				})
				.hide()
				.appendTo($face);

			return $face;
		}

		function start_tagging()
		{
			$photo.addClass('tagging-enabled');

			$toggle.addClass('active');

			$photo.find('.face').each(function() {
				make_face_editable($(this));
			});
		}

		function stop_tagging()
		{
			$photo.removeClass('tagging-enabled');

			$toggle.removeClass('active');

			$photo.find('.face')
				.resizable('destroy')
				.draggable('destroy')
				.off('click focus')
				.find('.delete-button').remove().end()
				.find('.tag-search').remove().end()
				.end();
		}

		function tagging_enabled() {
			return $photo.hasClass('tagging-enabled');
		}

		$toggle.change(function() {
			if (this.checked)
				start_tagging();
			else
				stop_tagging();
		});

		// Disable the face tagging when the photo is no longer visible
		// (i.e. when the popup moves to the next/prev photo)
		$(document).on('detach', function(e) {
			if ($.contains(e.target, $photo.get(0))) {
				if (tagging_enabled()) {
					$toggle.prop('checked', false);
					stop_tagging();
				}
			}
		});

		$photo.click(function(e) {
			if (!$photo.hasClass('tagging-enabled'))
				return;

			if (cancelNextClick) {
				cancelNextClick = false;
				return;
			}

			if (e.offsetX === undefined)
				e.offsetX = e.pageX - $photo.offset().left;

			if (e.offsetY === undefined)
				e.offsetY = e.pageY - $photo.offset().top;
			
			var pos = {
				top: Math.max(e.offsetY - 50, 0),
				left: Math.max(e.offsetX - 50, 0)
			};

			var data = [
				{name: 'x', value: pos.left / $photo.width()},
				{name: 'y', value: pos.top / $photo.height()},
				{name: 'w', value: 100 / $photo.width()},
				{name: 'h', value: 100 / $photo.height()}
			];

			$face = $('<div class="face untagged">')
				.css({
					position: 'absolute',
					top: pos.top,
					left: pos.left,
					width: 100,
					height: 100
				})
				.appendTo($photo);

			$face.append('<div class="tag-label"><a class="name">Not tagged</a></div>');

			$.post($photo.data('create-action'), data, function(resp) {
				if (resp.errors) {
					alert("Errors:\n" + resp.errors.join("\n"));
					return;
				}

				$face.attr('id', 'face_' + resp.iter.__id);
				$face.data('update-action', resp.iter.__links.update);
				$face.data('delete-action', resp.iter.__links.delete);
				make_face_editable($face);
			}, 'json');
		});

		$photo.on('click', '.face.untagged .tag-label', function(e) {
			if ($photo.hasClass('tagging-enabled'))
				return;

			e.preventDefault();
			e.stopPropagation();

			var $face = $(this).closest('.face');
			
			$toggle.prop('checked', true).change();
			
			$face.click();
		});
	});
});

jQuery.fn.inlineEdit = function(options) {
	$(this).each(function() {
		var $el = $(this);
		var $textfield = $('<input>').attr({'type': 'text'}).css({'font': 'inherit', 'margin': 0, 'padding': 0, 'border': 'none'});
		var value = $el.text();

		var visible = false;

		var show = function() {
			$el.empty();
			
			$textfield.val(value);
			$textfield.prop('disabled', false);
			$el.append($textfield);

			$textfield.focus();
			visible = true;

			return $.Deferred().resolve(value);
		}

		var hide = function(val) {
			visible = false;
			$textfield.detach();
			return $.Deferred().resolve(val !== undefined ? val : value);
		}

		var block = function() {
			$textfield.prop('disabled', true);
			return $.Deferred().resolve($textfield.val());
		}

		var save = function(val) {
			return options.save.call($el, val).then(function(val) { return value = val; });
		}

		var render = function(val) {
			var content = options.render.call($el, val);
			$el.empty();
			$el[typeof content == 'string' || content instanceof String ? 'text' : 'append'](content);
		}

		var accept = function() {
			return block().then(save).then(hide).then(render);
		}

		var cancel = function() {
			return hide().then(render);
		}

		render(value);

		$el.on('click', function(e) {
			if (!visible) {
				e.preventDefault();
				show();
			}
		});

		$textfield.on('blur', function(e) {
			if (visible) {
				value != $textfield.val() ? accept() : cancel();
			}
		});

		$textfield.on('keydown', function(e) {
			if (visible) {
				switch (e.keyCode) {
					case 13: // enter
						e.preventDefault();
						accept();
						break;
					case 27: // escape
						e.preventDefault();
						cancel();
						break;
				}
			}
		});
	})
}

/* Allow editing of photo titles */
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('.photo-title[data-update-link]')
		.addClass('editable')
		.inlineEdit({
			save: function(newTitle) {
				var $el = $(this);
				return $.post($(this).data('update-link'), {'beschrijving': newTitle})
					.success(function() {
						// Update the thumbnail as well
						var photo_id = $el.closest('#foto').data('photo-id');
						var $thumb = $('#photo_' + photo_id + ' > a');
						var $description = $thumb.children('.description');
						if ($description.length == 0)
							$description = $('<span>').addClass('description').appendTo($thumb);
						console.log($el, photo_id, $description);
						$description.text(newTitle);
					})
					.then(function() { return newTitle; });
			},
			render: function(title) {
				return title != '' ? title : $('<em>').text('Click to add a title');
			}
		});
});

/* When hovering over references to faces in photos, highlight the face */
$(document).on('mouseover', '[data-face-id]', function(e) {
	$('#face_' + $(this).data('face-id')).addClass('highlight');
});

$(document).on('mouseout', '[data-face-id]', function(e) {
	$('#face_' + $(this).data('face-id')).removeClass('highlight');
});