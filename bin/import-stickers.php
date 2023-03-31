#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'src/init.php';

$sticker_model = get_model('DataModelSticker');

function import_kml($file)
{
	global $sticker_model;

	$dom = simplexml_load_file($file);

	foreach ($dom->Document->Placemark as $placemark)
	{
		list($lng, $lat, $z) = explode(',', $placemark->Point->coordinates);

		$sticker_model->addSticker(
			(string) $placemark->name,
			(string) strip_tags($placemark->description),
			(double) $lat,
			(double) $lng);
	}
}

for ($i = 1; $i < $argc; ++$i)
	import_kml($argv[$i]);