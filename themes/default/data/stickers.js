$(document).on('ready partial-content-loaded', function(e) {
	var ensureMapsIsLoaded = function() {
		try {
			return !!google.maps;
		} catch (e) {
			var el = document.createElement('script');
			el.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBN22N-bX3aSaGfy9w9-oeUsnFRlB-1FiI&sensor=false';
			document.body.appendChild(el);
		}
	};

	$(this).find('.add-sticker-form').each(function(e) {
		ensureMapsIsLoaded();

		var $form = $(this);

		var canvas = $form.find('.sticker-location-map');

		if (canvas.data('picker'))
			return canvas.data('picker');

		var mapOptions = {
			center: new google.maps.LatLng(53.20, 6.56),
			zoom: 11,
			streetViewControl: false,
			mapTypeControl: true
		};

		// The small map
		var map = new google.maps.Map(canvas.get(0), mapOptions);

		// The draggable marker
		var marker = new google.maps.Marker({
			position: new google.maps.LatLng(53.20, 6.56),
			draggable: true,
			map: map
		});

		var updatePositionFields = function() {
			$form.find('input[name=lat]').val(marker.getPosition().lat());
			$form.find('input[name=lng]').val(marker.getPosition().lng());
		};

		// When right-clicking somewhere on the map, place the marker at that point
		google.maps.event.addListener(map, 'rightclick', function(e) {
			marker.setPosition(e.latLng);
		});

		// Update the hidden input fields when the marker changes position
		google.maps.event.addListener(marker, 'position_changed', updatePositionFields);

		// Make the map easily available through the dom element
		var picker = {
			map: map,
			marker: marker
		};
		
		canvas.data('picker', picker);

		if ($('#sticker-map')) {
			picker.map.setCenter($('#sticker-map').data('google-map').getCenter());
			picker.map.setZoom($('#sticker-map').data('google-map').getZoom());
			picker.marker.setPosition($('#sticker-map').data('google-map').getCenter());
		}

		$(window).on('resize', function() {
			google.maps.event.trigger(picker, 'resize');
		});
	});
});