jQuery(function($) {
	$.ajax('https://cache.svcover.nl/').then(function(response) {
		var status = response.match(/<p>.*\bopen\b.*<\/p>/i) ? 'open' : 'closed';

		if (status != 'open')
			return;

		var label = $('<a>')
			.attr('href', 'https://cache.svcover.nl/')
			.addClass('cover-room-' + status)
			.text('the Cover room is')
			.append($('<span>').text(status));
		$('.world > .header').append(label);
	});
});