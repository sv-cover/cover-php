<?php

$sticker_model = get_model('DataModelStickers');

$focussed_sticker = $sticker_model->getRandomSticker();

$nearby_stickers = $sticker_model->getNearbyStickers($focussed_sticker, 20);

function latRad($lat)
{
	$sin = sin($lat * M_PI / 180);
	$radX2 = log((1 + $sin) / (1 - $sin)) / 2;
	return max(min($radX2, M_PI), -M_PI) / 2;
}

function zoom($mapPx, $worldPx, $fraction)
{
	return floor(log($mapPx / $worldPx / $fraction) / log(2));
}

function getBoundsZoomLevel(Geokit\Bounds $bounds, $map_width, $map_height)
{
	$world_width = 256;
	$world_height = 256;
	$zoom_max = 21;

	$ne = $bounds->getNorthEast();
	$sw = $bounds->getSouthWest();

	$latFraction = (latRad($ne->getLatitude()) - latRad($sw->getLatitude())) / M_PI;

	$lngDiff = $ne->getLongitude() - $sw->getLongitude();
	$lngFraction = (($lngDiff < 0) ? ($lngDiff + 360) : $lngDiff) / 360;

	$latZoom = zoom($map_height, $world_height, $latFraction);
	$lngZoom = zoom($map_width, $world_width, $lngFraction);

	return min($latZoom, $lngZoom, $zoom_max);
}

$center = new Geokit\LatLng($focussed_sticker->get('lat'), $focussed_sticker->get('lng'));

$bounds = array_reduce($nearby_stickers, function($bounds, $sticker) {
	return $bounds->extendByLatLng(new Geokit\LatLng($sticker->get('lat'), $sticker->get('lng')));
}, new Geokit\Bounds($center, $center));

$zoom = getBoundsZoomLevel($bounds, 1920, 1080);

$center_marker = sprintf('%f,%f', $focussed_sticker->get('lat'), $focussed_sticker->get('lng'));

$surrounding_markers = array_map(function($sticker) {
	return sprintf('%f,%f', $sticker->get('lat') , $sticker->get('lng'));
}, $nearby_stickers);

$detail_map_url = sprintf('http://maps.googleapis.com/maps/api/staticmap?center=%f,%f&zoom=%d&size=640x360&sensor=false&maptype=hybrid&key=AIzaSyBN22N-bX3aSaGfy9w9-oeUsnFRlB-1FiI&scale=2&markers=%s&markers=%s',
	$focussed_sticker->get('lat'), $focussed_sticker->get('lng'), $zoom - 2,
	'color:red' . rawurlencode('|' . $center_marker),
	'color:blue' . rawurlencode('|' . implode('|', $surrounding_markers))
);

?>
<h1 class="header">Cover Sticker map <small>Add yours at svcover.nl/stickers.php</small></h1>
<img src="<?=$detail_map_url?>" width="100%">
<h1 style="position: absolute; top: 50%; font: 36px/36px sans-serif; left: 0; right: 0; text-align:center;"><?=$focussed_sticker->get('label')?></h1>
