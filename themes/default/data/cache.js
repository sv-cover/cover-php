jQuery(function($) {
	$.ajax('https://cache.svcover.nl/').then(function(response) {
		var status = response.match(/\bopen\b/i) ? 'open' : 'closed';

		if (status != 'open')
			return;

		var label = $('<span>')
			.addClass('cover-room-' + status)
			.text(status)
			.css({
				'position': 'absolute',
				'top': '35px',
				'left': '260px',
				'font-size': '1.7em',
				'text-transform': 'uppercase',
				'display': 'inline-block',
				'background': '#C8102E',
				'color': 'white',
				'padding': '4px 18px',
				'border-radius': '4px',
				'box-shadow': '0 0 0 2px white, 0 0 0 4px #C8102E, 0 0 4px 4px rgba(0, 0, 0, 0.6)',
				'transform': 'rotate(-7deg)'
			});
		$('.world > .header').append(label);
	});
});