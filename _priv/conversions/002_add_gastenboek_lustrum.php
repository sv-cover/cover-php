<?php
	include('init.php');

	title('Add gastenboek lustrum conversion');
	
	$db = get_db();
	
	/* Add the column with the new type */
	message('Add lustrum column');
	$res = $db->query('ALTER TABLE gastenboek ADD COLUMN lustrum smallint default 0');
	result($res);	
?>
