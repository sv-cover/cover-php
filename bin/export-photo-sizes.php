#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'src/init.php';
require_once 'src/framework/terminal.php';

$photo_model = get_model('DataModelPhotobook');

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
	$photo = $photos[$i];

	try {
		if ($photo->original_has_changed() || $options['force'] || !$photo->get('width') || !$photo->get('height'))
		{
			$size = $photo->compute_size();
			
			$photo->set_all($size);
			$photo->set('filehash', $photo->compute_hash());
		}
		else
			$size = $photo->get_size();

		if (!$photo->get('created_on') || $options['force'])
			$photo->set('created_on', $photo->compute_created_on_timestamp());
		
		$photo_model->update($photo);
		printf("(% 2d%%) %d: %dx%d %s\n", round($i / count($photos) * 100), $photo->get_id(),
			$size['width'], $size['height'], $photo->get_full_path());
	}
	catch (Exception $e) {
		printf("%d: Caught exception:\n%s\n", $photo->get_id(), $e);
	}
}
