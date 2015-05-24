#!/usr/bin/env php
<?php
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

printf("Measuring %d photos...\n", count($photos));

for ($i = 0; $i < count($photos); ++$i)
{	
	try {
		if ($options['force']) {
			$photos[$i]->set('width', null);
			$photos[$i]->set('height', null);
		}
		
		$size = $photos[$i]->get_size();
		printf("%d: %dx%d %s\n", $photos[$i]->get_id(), $size[0], $size[1], $photos[$i]->get_full_path());
	}
	catch (Exception $e) {
		printf("%d: Caught exception:\n%s\n", $photos[$i]->get_id(), $e);
	}
}
