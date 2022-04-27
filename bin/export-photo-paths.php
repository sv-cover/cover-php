#!/usr/bin/env php
<?php
ini_set('memory_limit', '512M');
chdir(dirname(__FILE__) . '/..');

require_once 'src/init.php';

$translation_table = array();

function get_university_year($date)
{
	$timestamp = strptime($date, '%d-%m-%Y');

	if (!$timestamp)
		return null;

	$year = $timestamp['tm_year'] + 1900;

	// 21-6-2013 -> 2012/2013
	// 21-10-2013 -> 2013/2014
	// if (mktime(0, 0, 0, $timestamp['tm_mon'], $timestamp['tm_day'] + 1, $year) < mktime(0, 0, 0, 8, 1, $year))
	// 	$year -= 1;

	return $year;
}

function normalize_filename($filename)
{
	return preg_match('/^(?<year>\d{4})[_-]?(?<month>\d{2})[_-]?(?<day>\d{2})[-_]?(?<time>\d{6})/', $filename, $match)
		? sprintf('%04d%02d%02d-%s.jpg', $match['year'], $match['month'], $match['day'], $match['time'])
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

function find_path_using_exif($guessed_path)
{
	global $translation_table;

	$search_path = dirname($guessed_path);

	$guessed_filename = normalize_filename(basename($guessed_path));

	if (!isset($translation_table[$search_path]))
	{
		$translation_table[$search_path] = array();

		echo ">> Indexing $search_path\n";

		foreach (new FilesystemIterator($search_path) as $photo)
		{
			if (!preg_match('/\.(jpg|gif)$/i', $photo))
				continue;

			$exif_filename = filename_from_exif($photo);

			$i = 0;
			while (isset($translation_table[$search_path][$exif_filename])) {
				$alt_filename = filename_with_letter($exif_filename, $i++);
				if (!isset($translation_table[$search_path][$alt_filename])) {
					echo "Indexed $exif_filename as $alt_filename\n";
					$exif_filename = $alt_filename;
					break;
				}
			}

			$translation_table[$search_path][$exif_filename] = $photo;
		}
	}

	return isset($translation_table[$search_path][$guessed_filename])
		? $translation_table[$search_path][$guessed_filename]
		: null;
}

function find_path($model, DataIterPhoto $photo, array &$tried = array())
{
	// $photos_root = '/home/commissies/photocee/fotosGroot/';
	$photos_root = get_config_value('path_to_photos');

	if (substr($photos_root, -1, 1) != '/')
		$photos_root .= '/';

	$common_path = 'svcover.nl/fotos/';

	if (($path = strstr($photo->get('url'), $common_path)) === false)
		return null;

	$path_parts = explode('/', substr($path, strlen($common_path)));

	$path_parts_copy = $path_parts;

	while (count($path_parts) > 0)
	{
		$possible_path = $photos_root . implode('/', $path_parts);
		$tried[] = $possible_path;

		if (file_exists($possible_path))
			return substr($possible_path, strlen($photos_root));

		// But if it is a directory with jpeg files try finding the file by exif data
		if (file_exists(dirname($possible_path)) && count(glob(dirname($possible_path) . '/*.[jJ][pP][gG]')) > 0)
			if ($exif_found_path = find_path_using_exif($possible_path))
				return $exif_found_path;

		array_shift($path_parts);
	}

	// typical paths are fotosGroot/fotos20092010/YYYYMMDD_*/folder/photo.jpg
	$book = $photo->get_book();

	$parents = $model->get_parents($book);

	array_push($parents, $book);

	// Remove first two books (root and college year)
	$root_book = array_shift($parents);
	$path = $photos_root;

	// College year book is named using the year of the book (duh!)
	$year_book = array_shift($parents);
	$year = get_university_year(end(array_filter($parents, function ($b) { return (bool) $b->get('date'); }))->get('date'));
	$path .= sprintf('fotos%d%d/', $year, $year + 1);

	// Activity book is based named using the date of the book, but it
	// also has a name that may not be the same as the book on the website.
	$activity_book = array_shift($parents);
	
	if ($activity_book->get('titel') == 'Chantagemap') {
		if (file_exists($path . $year . '0101_chantagemap'))
			$path .= $year . '0101_chantagemap/';
		elseif (file_exists($path . 'chantagemap/'))
			$path .= 'chantagemap/';
		else
			throw new Exception("Could not find chantagemap");
	}
	else {
		if (!preg_match('/^(?<day>\d{1,2})-(?<month>\d{1,2})-(?<year>\d\d\d\d)$/', $activity_book->get('date'), $match)
		&& !preg_match('/^(?<year>\d\d\d\d)-(?<month>\d{1,2})-(?<day>{1,2})$/', $activity_book->get('date'), $match))
			throw new Exception("Could not match activity date to common pattern");

		$path .= sprintf('%04d%02d*/', $match['year'], $match['month'], $match['day']);
	}

	// So after generating a path, more or less, we just glob our way through
	$tried[] = $path;
	foreach (glob($path) as $folder)
	{
		$foldername = basename($folder);

		if (!preg_match('/chantagemap/i', $foldername))
		{
			if (!preg_match('/(?<year>\d{4})(?<month>\d{2})(?<day>\d{2})(?:(?<op>en|tm)(?<end>\d{2}))?_/', $foldername, $fmatch))
				die("Kut met $foldername\n");

			if ($fmatch['end'] === null) {
				if ($fmatch['day'] != $match['day'])
					continue;
			}
			else {
				if ($fmatch['day'] > $match['day'] || $fmatch['end'] < $match['day'])
					continue;
			}
		}

		// and now just YOLO find the filename
		$path_parts = explode('/', substr($path, strlen($common_path)));

		$tried[] = $folder . end($path_parts_copy);

		// It might be in this folder already
		if (file_exists($folder . end($path_parts_copy)))
			return substr($folder . end($path_parts_copy), strlen($photos_root));
		elseif ($exif_found_path = find_path_using_exif($folder . end($path_parts_copy)))
			return $exif_found_path;

		// But it also might hide in some subfolder
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $subfolder)
		{
			if (!is_dir($subfolder))
				continue;
			
			$tried[] = $subfolder . '/' . end($path_parts_copy);

			if (file_exists($subfolder . '/' . end($path_parts_copy)))
				return substr($subfolder . '/' . end($path_parts_copy), strlen($photos_root));
			elseif ($exif_found_path = find_path_using_exif($subfolder . '/' . end($path_parts_copy)))
				return $exif_found_path;
		}
	}

	return null;
}

$photo_model = get_model('DataModelPhotobook');

$photos = $photo_model->find('filepath IS NULL');

$force = $argc > 1 && ($argv[1] == '--force' || $argv[1] == '-f');

printf("Finding %d photos\n", count($photos));

foreach (($photos) as $photo)
{
	$tried = array();

	if ($photo->get('filepath') && !$force)
		continue;

	try {
		if ($path = find_path($photo_model, $photo, $tried)) {
			printf("%d\t%s\n", $photo->get_id(), $path);

			$photo->set('filepath', $path);
			$photo_model->update($photo);

			// Reset the history because otherwise we run into memory errors :(
			get_db()->history = array();
		}
		else {
			printf("%d\tCould not guess path for book %s (%s)\n", $photo->get_id(),
				implode('/', array_map(function($book) { return $book->get('titel'); },
					array_merge($photo_model->get_parents($photo->get_book()), [$photo->get_book()]))),
				$photo->get_book()->get('date'));

			echo "Searched in:\n";
			array_walk($tried, function($path) { echo "$path\n"; });
		}
	} catch (Exception $e) {
		printf("%d\tCaught an error for book %s (%s):\n", $photo->get_id(),
			implode('/', array_map(function($book) { return $book->get('titel'); },
				array_merge($photo_model->get_parents($photo->get_book()), [$photo->get_book()]))),
			$photo->get_book()->get('date'));

		echo "$e\n";
	}
}
