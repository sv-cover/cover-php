#!/usr/bin/env php
<?php
ini_set('memory_limit', '512M');
chdir(dirname(__FILE__) . '/..');

require_once 'src/init.php';

$translation_table = array();

function normalize_filename($filename)
{
	return preg_match('/^(?<year>\d{4})[_-]?(?<month>\d{2})[_-]?(?<day>\d{2})[-_]?(?<time>\d{6})(?<version>[a-z]?)/', $filename, $match)
		? sprintf('%04d%02d%02d-%s%s.jpg', $match['year'], $match['month'], $match['day'], $match['time'], $match['version'] ? $match['version'] : '')
		: $filename;
}

function filename_from_exif($photo)
{
	$data = exif_read_data($photo);

	if (isset($data['DateTimeOriginal'])) {
		$timestamp = strtotime($data['DateTimeOriginal']);
		return strftime('%Y%m%d-%H%M%S.jpg', $timestamp);
	} elseif (isset($data['FileDateTime'])) {
		return strftime('%Y%m%d-%H%M%S.jpg', $data['FileDateTime']);
	} else {
		return normalize_filename(basename($photo));
	}
}

function filename_with_letter($filename, $index)
{
	return substr($filename, 0, -4) . range('a', 'z')[$index] . substr($filename, -4, 4);
}

$recursive = false;

$photo_model = get_model('DataModelPhotobook');

$photos = $photo_model->find(sprintf('boek = %d', $argv[1]));

printf("Finding %d photos\n", count($photos));

$index = array();

$folder = isset($argv[2])
	? $argv[2]
	: dirname($photos[0]->get('filepath'));

$iter = $recursive
	? new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($folder,
			FilesystemIterator::KEY_AS_PATHNAME |
			FilesystemIterator::CURRENT_AS_FILEINFO |
			FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST)
	: new FilesystemIterator($folder);

foreach ($iter as $file) {

	echo ">>> $file\n";

	if (!preg_match('/\.(jpg|gif)$/i', $file))
		continue;

	$exif_filename = filename_from_exif($file);

	if (!$exif_filename) {
		echo "Could not determine exif filename for <$file>\n";
		continue;
	}

	$i = 0;
	while (isset($index[$exif_filename])) {
		$alt_filename = filename_with_letter($exif_filename, $i++);
		if (!isset($index[$alt_filename])) {
			echo "Indexed $file as $alt_filename\n";
			$exif_filename = $alt_filename;
			break;
		}
	}

	$index[$exif_filename] = $file;
}

foreach ($photos as $photo)
{
	$photo_filename = normalize_filename(basename($photo->get('url')));

	if (isset($index[$photo_filename])) {
		$path = $index[$photo_filename];
		printf("%d\t%s\n", $photo->get_id(), $path);
		
		$path = substr($path, strlen(get_config_value('path_to_photos')));
		
		$photo->set('filepath', $path);
		$photo_model->update($photo);

		// Remove that path from the index so it cannot be assigned twice
		unset($index[$photo_filename]);
		
		// Reset the history because otherwise we run into memory errors :(
		get_db()->history = array();
	} else {
		printf("! %d\t%s not found\n", $photo->get_id(), $photo->get('url'));
	}
}
