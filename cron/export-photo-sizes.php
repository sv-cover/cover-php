#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';

$photo_model = get_model('DataModelFotoboek');

$photos = $photo_model->get();

for ($i = 0; $i < count($photos); ++$i)
{	
	if ($size = $photos[$i]->get_size())
		printf("UPDATE fotos SET width = %d, height = %d WHERE id = %d\n",
			$size[0], $size[1], $photos[$i]->get_id());
	else
		printf("-- Skipping size of %d\n", $photos[$i]->get_id());

	if ($photos[$i]->get_thumb_size())
		printf("UPDATE fotos SET thumbwidth = %d, thumbheight = %d WHERE id = %d\n",
			$size[0], $size[1], $photos[$i]->get_id());
	else
		printf("-- Skipping thumb size of %d\n", $photos[$i]->get_id());
}
