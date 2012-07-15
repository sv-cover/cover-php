<?php
	include('init.php');
	
	title('Add profielen sequence conversion');

	$db = get_db();

	message('Get max id from profielen');
	$max = $db->query_value('SELECT MAX(id) + 1 FROM profielen');
	ok();
	
	message('Create new sequence called profielen_id_seq');
	$res = $db->query('create sequence public.profielen_id_seq start with ' . $max);
	result($res);
	
	message('Set id type to new sequence');
	$res = $db->query('alter table profielen alter column id set default nextval(\'profielen_id_seq\'::regclass)');
	result($res);
?>	
