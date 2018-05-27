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

/**
 * Creates a throttelable version of a function. Calling the 'throttled' function
 * directly will call the function directly (and clear any scheduled delayed call)
 * but you can also create a new delayed callback by calling .delay(500) on the
 * throttled function:
 * 
 * Example:
 *   var fun = throttle(updateCallback);
 *   $('input').on('keyup', fun.delay(500));
 * 
 * This will call updateCallback 500ms after the last keyup event. However, if
 * you call fun() directly after the last keyup, it won't be called a second
 * time when the 500ms delay timer runs out.
 */
function throttle(callback)
{
	var timeout = null;

	var caller = function() {
		clearTimeout(timeout);
		callback();
	};

	caller.delay = function(delay) {
		return function() {
			clearTimeout(timeout);
			timeout = setTimeout(callback, delay);
		};
	};

	return caller;
}

jQuery(function($) {
	$('.hide-by-default').hide();

	$(document).on('click', "a[href^='#']", function(e) {
		var match = this.href.match(/(#.+)$/);
		if (match)
			$(match[1]).show();
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
// Note that this function triggers the partial-content-loaded event on the loaded
// content, which is used by many functions here to attach event listeners to the
// new stuff.
jQuery(function($) {
	var inline_link_handler = function(e) {
		// Do not disturb any effect of modifier keys
		if (e.ctrlKey || e.shiftKey || e.metaKey)
			return;

		e.preventDefault();

		var $target = null;

		if ($(this).data('placement-selector') == 'modal') {
			var $modal = $('<div class="modal" tabindex="0">'),
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

			$modal.focus();

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

// If a link in a modal links to the current page, make that link just close the modal
$(document).on('partial-content-loaded', function(e) {
	var $modal = $(e.target).closest('.modal');
	if ($modal.length) {
		$(e.target).find('a').each(function() {
			if (this.href == document.location.href) {
				$(this).click(function(e) {
					e.preventDefault();
					$modal.remove();
				});
			}
		});
	}
});

// If a link has the attribute data-image-popup, and its value is 'modal', open
// the image (href attribute) it points to in a modal popup.
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
		var masterSwitch = $(fieldset).find('legend input[type=checkbox], legend input[type=radio]');

		if (masterSwitch.length === 0)
			return;

		$(fieldset).addClass('jquery-fieldset');

		var fields = $(fieldset).find('input, select').not(masterSwitch);

		var update = function() {
			fields.prop('disabled', !masterSwitch.is(':checked'));
		};

		update();

		$(e.target).find('input[name="' + masterSwitch.attr('name') + '"]').on('change', update);
	});
});

$(document).on('ready partial-content-loaded', function(e) {
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

		$foto.find('.face').each(function() {
			make_face($(this));
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

		// Give every face a small model (with a single method: accept)
		function make_face($face)
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

			$face.data('model', {accept: accept});
		}

		function make_face_editable($face)
		{
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

			$face.data('model').focus = function() {
				update_names_list();
				$face.find('.name').hide();
				$face.find('.tag-search').show().focus();
			};

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
							$face.data('model').accept(null, $(this).val());
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
						$face.data('model').accept(ui.item.id, ui.item.name);
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
				make_face($face);
				make_face_editable($face);
				$face.data('model').focus();
			}, 'json');
		});

		$photo.on('click', '.face.untagged .tag-label .suggested-face button', function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();

			var $face = $(this).closest('.face');
			var $suggestion = $(this).closest('.suggested-face').remove();

			if ($(this).hasClass('yes')) {
				$face.data('model').accept($suggestion.data('member-id'), $suggestion.find('.name').text());
			} else {
				$toggle.prop('checked', true).change();
				$face.data('model').focus();
			}
		});

		$photo.on('click', '.face.untagged .tag-label > .name', function(e) {
			if ($photo.hasClass('tagging-enabled'))
				return;

			e.preventDefault();
			e.stopImmediatePropagation();

			var $face = $(this).closest('.face');
			
			$toggle.prop('checked', true).change();
			
			$face.data('model').focus();
		});

		$photo.on('click', '.face', function(e) {
			if (!$photo.hasClass('tagging-enabled'))
				return;

			e.preventDefault();
			e.stopPropagation();
			
			$(this).data('model').focus();
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

$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('.contenteditable').each(function() {
		var $editable = $(this),
			$previewButton = $(this).find('.editable-mode-preview'),
			$editButton = $(this).find('.editable-mode-edit'),
			$form = $(this).find('form[name=editable]'),
			$editPane = $(this).find('.editable_content'),
			$previewPane = $(this).find('.editable_preview');

		var switchMode = function(mode) {
			$editButton.toggle(mode == 'preview');
			$editPane.toggle(mode == 'edit');
			$previewButton.toggle(mode == 'edit');
			$previewPane.toggle(mode == 'preview');
		};

		var updatePreview = function() {
			$previewPane.text('Loading…');

			$.post('show.php?preview', $form.serialize()).then(function(response) {
				$previewPane.html(response);
			});
		};

		$(this).find('.editable-save').click(function() {
			var url = $form.prop('action').replace(/#.*/, '') + '&xmlrequest=1';
			$.post(url, $form.serialize())
				.then(function(response) {
					alert(response);
				});
		});

		$(this).find('.editable-revert').click(function() {
			$form.reset();
		});

		$(this).find('form[name=editable_language] select').change(function() {
			this.form.submit();
		});

		$editButton.click(function() {
			switchMode('edit');
		});

		$previewButton.click(function() {
			switchMode('preview');
			updatePreview();
		});

		switchMode('edit'); // default to edit mode
	});
});

// Tab panes
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('.tab-container').each(function() {
		var $tabs = $(this).find('.nav-tabs li');
		var $panels = $(this).find('.tab-panel');

		var urlHash = function(url) {
			var pos = url.indexOf('#');
			return pos > -1 ? url.substr(pos + 1) : null;
		}

		var switchTab = function(tabId) {
			$tabs.each(function() {
				$(this).toggleClass('active', urlHash($(this).find('a').attr('href')) == tabId);
			});
			$panels.each(function() {{
				var visible = $(this).prop('id') == tabId;
				var event = null;
				
				if (visible && !$(this).is(':visible'))
					event = 'show';
				else if (!visible && $(this).is(':visible'))
					event = 'hide';

				$(this).toggle(visible);
				if (event)
					$(this).trigger(event);
			}});
		}

		$tabs.find('a').click(function(e){
			switchTab(urlHash($(this).attr('href')));
			e.preventDefault();
		});

		// Find the first active tab
		var $activeTab = $tabs.filter('.active').first();

		// If none is explicitly active, go to the first tab in general
		if ($activeTab.length == 0)
			$activeTab = $tabs.first();

		// Mark that tab as the active tab (and hide all the others)
		switchTab(urlHash($activeTab.find('a').attr('href')));
	});
});

// Datalist like behaviour for form.
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('input[data-suggestions]').each(function () {
		var suggestions = $(this).data('suggestions');
		$(this).autocomplete({
			source: suggestions,
			delay: 0,
			minLength: 0
		});
		$(this).on('focus', function() {
			$(this).autocomplete('search', '');
		});
	});
});

// When autofocus=end, put the cursor at the end of the text
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find("*[autofocus='end']").each(function() {
		var len = $(this).val().length;
		$(this).get(0).setSelectionRange(len, len);
		// No need to focus first, the autofocus attribute should have taken care of that already
	})
});

// Autocomplete for locations
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('input[data-autocomplete=location]').each(function () {
		$(this).autocomplete({
			minLength: 3,
			source: function(request, response) {
				$.getJSON('agenda.php', {
					'view': 'suggest-location',
					'search': request.term,
					'limit': 15
				}, response);
			},
			focus: function() {
				return false;
			}
		});
	});
});

/* Promotional banners */
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('.sign-up-banner').each(function() {
		var slides = $(this).find('.background');
		var currentSlide = 0;

		setInterval(function() {
			currentSlide = (currentSlide + 1) % slides.length;
			slides.removeClass("current");
			slides.eq(currentSlide).addClass("current");
		}, 3500);
	});
});

/* Committee battle banner */
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('.committee-battle-banner').each(function() {
		var columnCount = 5;
		var imageCount = 3;
		var paths = $(this).data('photos');
		var pathIndex = 0;

		var nextPath = function() {
			return paths[pathIndex++ % paths.length];
		};

		var columns = $.map(new Array(columnCount), function(value, columnIndex) {
			var column = $('<div>').addClass('column').css({
				left: 115 * (columnIndex / columnCount) - 35 + '%',
				width: (100 / columnCount) + '%',
				animationDuration: 60 + 10 * (columnIndex % 2) + 's',
				animationDelay: -1 * Math.random() + 's'
			});

			var images = $.map(new Array(imageCount), function(value, imageIndex) {
				return $('<img>').prop({
					src: nextPath(),
					width: 500,
					height: 300
				});
			});

			for (var i = 0; i < 2; ++i)
				images.push(images[i].clone());

			column.append(images);

			return column;
		});

		var overlay = $('<div>').css({
			position: 'absolute',
			top: 0,
			left: 0,
			bottom: 0,
			right: 0,
			background: '#000',
			zIndex: -1,
			opacity: 0.25
		});

		$(this).append(columns);

		$(this).append(overlay);

		setTimeout(function() {
			$(columns).each(function() {
				$(this).css({
					animationName: 'banner-scroll'
				});
			});
		}, 100);
	});
});

/* Click to read more */
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('.click-to-read-on').each(function() {
		var $div = $(this).addClass('collapsed');
		var $button = $('<button>')
			.addClass('button read-on-button')
			.text($div.data('read-on-label') || 'Click to Read On')
			.click(function() {
				$div.removeClass('collapsed');
			});
		$div.append($button);
	})
});

/* Enable select2 automatically */
$(document).on('ready partial-content-loaded', function(e) {
	if (jQuery.fn.select2) {
		$(e.target).find('select[name="member_ids[]"]').select2({
			templateResult: function(option) {
				if (!option.id)
					return document.createTextNode(option.text);

				var img = document.createElement('img');
				img.className = 'profile-image';
				img.src = 'foto.php?lid_id=' + option.id + '&format=square&width=50';
				var text = document.createTextNode(option.text);
				var span = document.createElement('span');
				span.appendChild(img);
				span.appendChild(text);
				return span;
			}
		});
	}

	// Check all checkboxes in the form that have a data-member-ids attribute
	// to see if it contains one of the currently selected members. If so, check it.
	$(e.target).find('[name="member_ids[]"]').each(function() {
		var $field = $(this);
		var $form = $(this.form);

		var $legend = $(this.form)
			.find('input[type=checkbox][data-member-ids]')
			.first()
			.closest('fieldset')
			.find('legend')
			.first();

		var $method = $('<select>\
			<option value="some">Union of</option>\
			<option value="every">Intersection of</option>\
		</select>').prependTo($legend);

		var update = function() {
			var value = $field.val();
			var method = $method.val();
			console.log(value, method);
			$form.find('input[type=checkbox][data-member-ids]').prop('checked', function() {
				// Test whether this set of member-ids is completely or partially contained by value.
				var member_ids = $(this).attr('data-member-ids').split(' ');
				return value !== null && value[method](function(member_id) {
					return member_ids.indexOf(member_id) !== -1;
				});
			});
		};

		$field.add($method).on('change', update);
	});
});

// Load photo book photos only once they are (almost) in view
// to speed up loading photo book pages
$(document).on('ready partial-content-loaded', function(e) {
	var updateScheduled = false;

	var threshold = Math.max(300, $(window).height()); // pixels 'below the fold' where images should start loading

	var emptyPixel = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

	function isHidden() {
		var $img = $(this);

		// Keep hidden images for now
		if ($img.is(':hidden'))
			return true;

		var windowTop = $(window).scrollTop(),
			windowBottom = windowTop + $(window).height(),
			elementTop = $img.offset().top,
			elementBottom = elementTop + $img.height();

		return !(elementBottom >= windowTop - threshold && elementTop <= windowBottom + threshold);
	}

	function load() {
		this.src = $(this).data('src');
	}

	function update() {
		// Load all images that are not hidden
		var loaded = $images.not(isHidden).each(load);

		// And remove those images from the set
		$images = $images.not(loaded);

		updateScheduled = false;
	};

	// Find all images
	var $images = $(e.target).find('.photos img[data-src]');

	// Remove their src attribute (and store it in the data-src attribute for now)
	$images.each(function() {
		$(this).prop('src', $(this).data('placeholder-src') || emptyPixel);
	});

	// Very rudimentary polyfill for requestAnimationFrame
	var schedule = window.requestAnimationFrame || function (callback) { return setTimeout(callback, 16); };

	// Schedule an update right now to load those initial images
	update();

	// On scroll and resize we 'schedule' an update run
	$(window).on('scroll resize', function() {
		if (!updateScheduled) {
			schedule(update);
			updateScheduled = true;
		}
	});
});

// Previews of editable content and forum topics. Use data-preview-source attribute
// to select which element to take the input value from, and use data-preview-url
// to select to which url to make a post request. It will send all the form data
// with it to that endpoint, and display the response in the element itself.
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('[data-preview-source][data-preview-url]').each(function() {
		var $target = $(this);
		var $source = $($target.attr('data-preview-source'));
		var previewURL = $target.attr('data-preview-url');

		if ($source.length === 0) {
			console.warn('Could not find element', $target.attr('data-preview-source'));
			return;
		}

		var refresh = throttle(function() {
			// If the target isn't visible (e.g. different tab) then don't go
			// through all this hassle.
			if (!$target.is(':visible'))
				return;
			
			var data = $source.closest('form').serialize();
			$.post(previewURL, data).done(function(response) {
				$target.html(response);
			});
		});

		// Either update the field when we change stuff in the source field
		$source.on('keyup', refresh.delay(500));

		// Or when it becomes visible (e.g. different tab)
		$target.on('show', refresh);
	});
});

// Make elements sortable by using data-sortable-action and data-sortable-id attributes.
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('[data-sortable-action]').each(function() {
		var $list = $(this);

		$list.sortable({
			handle: '.sortable-drag-handle',
			update: function() {
				console.log($list.data('sortableAction'), $list.children().map(function() {
					return $(this).data('sortableId');
				}));
				$.post($list.data('sortableAction'), {
					order: $list.children().map(function() {
						return $(this).data('sortableId');
					}).get()
				});
			}
		});
	});
});

// Forms that autosubmit on change (just submit, no feedback)
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('form[data-submit-on-change]').each(function() {
		var extraData = $.map($(this).data('submitOnChange'), function(value, name) {
			return {name: name, value: value};
		});

		$(this).on('change', function(e) {
			$.ajax({
				url: $(this).attr('action'),
				method: $(this).attr('method').toUpperCase(),
				data: $(this).serializeArray().concat(extraData)
			});
		});

		$(this).addClass('submit-on-change');
	})
})

// Growing lists (i.e. the options list in form fields)
$(document).on('ready partial-content-loaded', function(e) {
	$(e.target).find('[data-growing-list]').each(function() {
		var $list = $(this);
		var $template = $(this).find('[data-template]').detach();

		function check() {
			if ($list.find('input').last().val() != '')
				$list.append($template.clone());
		}

		$list.on('input', function(e) {
			check();
		});

		$list.on('keydown', function(e) {
			if (e.keyCode == 8 && e.target.value == '') {// backspace in empty field
				e.preventDefault();

				var $child = $(e.target).closest($list.children());
				$child.prev($list.children()).each(function() {
					$(this).find('input:first-of-type').each(function() {
						this.setSelectionRange(this.value.length, this.value.length);
					});
				});
				$child.remove();
			}
		});

		$list.sortable({
			update: function() {
				$list.trigger('change');
			}
		});

		check();
	})
});