#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';

$photo_model = get_model('DataModelFotoboek');

$photos = $photo_model->get();

for ($i = 0; $i < count($photos); ++$i)
{
	printf("% 8d / % 8d (%d)\n",
		$i, count($photos), $photos[$i]->get_id());
	
	$photo->get_size();
	$photo->get_thumb_size();
}
