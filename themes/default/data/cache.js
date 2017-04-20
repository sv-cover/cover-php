jQuery(function($) {
	$.ajax('https://cache.svcover.nl/').then(function(response) {
			var status = response.match(/<p>.*\bopen\b.*<\/p>/i) ? 'open' : 'closed';

		if (status != 'open')
			return;

		var label = $('<span>')
			.addClass('cover-room-' + status)
			.text(status);
		$('.world > .header').append(label);
	});
});