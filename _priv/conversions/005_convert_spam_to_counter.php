<?php
	include('init.php');
	title('Convert spam to a counter');

	$db = get_db();
	
	message('Deleting old spam');
	$spam_count = $db->query_value("SELECT COUNT(*) FROM gastenboek WHERE spam = 1");
	$res = $db->query('DELETE FROM gastenboek WHERE spam = 1');
	result($res);
	
	message("Storing spam count");
	$model = get_model("DataModelConfiguratie");
	$model->set_value('spam_count', $spam_count);
	ok();
?>