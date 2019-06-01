jQuery(function($) {
	$.getJSON('https://cache.svcover.nl/json.php').then(function(response) {
		if (response.status != 'open')
			return;

		var label = $('<a>')
			.attr('href', 'https://cache.svcover.nl/')
			.addClass('cover-room-' + response.status)
			.text('the Cover room is')
			.append($('<span>').text(response.status));
		$('.world > .header').append(label);
	});
});