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
