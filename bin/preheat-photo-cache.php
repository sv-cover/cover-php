#!/usr/bin/env php
<?php
ini_set('memory_limit', '512M');
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';
require_once 'include/terminal.php';

$photo_model = get_model('DataModelFotoboek');

$options = array('force' => false, 'recursive' => false);

$book_ids = parse_options($argv, $options);

function array_flatten($arrays)
{
	if (!$arrays)
		return array();

	return call_user_func_array('array_merge', $arrays);
}

function get_book_photos($book_id)
{
	global $photo_model;
	return $photo_model->get_book($book_id)->get_photos();
}

function get_book_photos_recursive($book_id)
{
	global $photo_model;

	if ($book_id instanceof DataIterPhotobook)
		$book = $book_id;
	else
		$book = $photo_model->get_book($book_id);

	return array_merge($book->get_photos(),
			array_flatten(array_map('get_book_photos_recursive', $book->get_books())));
}

$photos = array_flatten(array_map($options['recursive'] ? 'get_book_photos_recursive' : 'get_book_photos', $book_ids));

// Just open the resource for each photo once for each scale and it should
// generate the temp files automatically.
foreach ($photos as $i => $photo)
{
	printf('(% 2d%%) %d: ', round($i / count($photos) * 100), $photo->get_id());
	foreach (get_config_value('precomputed_photo_scales', array()) as $dimesions)
	{
		fclose($photo->get_resource($dimesions[0], $dimesions[1], $options['force']));
		vprintf(' %dx%d', $dimesions);
	}
	echo "\n";
}