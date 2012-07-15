<?php
	include('init.php');

	title('Forum table layout conversion');

	$db = get_db();

	// Change replyto to thread in forum_replies
	$db->query('alter table forum_replies rename replyto to thread');
	
	// Drop subject column
	$db->query('alter table forum_replies drop column subject');
	
	// Copy forum_messages to forum_replies
	message('Copy forum_messages to forum_replies');

	$threads = $db->query('SELECT * FROM forum_messages');
	
	foreach ($threads as $thread) {
		$thread['thread'] = $thread['id'];
		unset($thread['id']);
		unset($thread['forum']);
		unset($thread['subject']);
		unset($thread['poll']);
		unset($thread['lastreply']);

		$db->insert('forum_replies', $thread);
	}
	
	ok();
	message('Re-id messages based on date');
	
	// Re-id the messages
	$max = $db->query_value('SELECT MAX(id) FROM forum_replies');
	$messages = $db->query('SELECT * FROM forum_replies order by date');
	
	// First make sure that id's fall out of the range we want to use
	foreach ($messages as $message) {
		$max = $max + 1;
		$db->update('forum_replies', array('id' => $max), 'id = ' . $message['id']);
	}
	
	$id = 0;
	$messages = $db->query('SELECT * FROM forum_replies order by date');
	
	// Now re-id them for real
	foreach ($messages as $message) {
		$id = $id + 1;
		$db->update('forum_replies', array('id' => $id), 'id = ' . $message['id']);
	}
	
	ok();
	message('Renaming tables');
	
	// Set new current on the sequence
	$db->query('alter sequence forum_replies_id_seq restart with ' . ($id + 1));
	
	// Rename table forum_messages to forum_threads
	$db->query('alter table forum_messages rename to forum_threads');
	$db->query('alter table forum_messages_id_seq rename to forum_threads_id_seq');
	$db->query('alter table forum_threads alter column id set default nextval(\'forum_threads_id_seq\'::regclass)');
	
	// Drop message column
	$db->query('alter table forum_threads drop column message');
	
	// Rename table forum_replies to forum_messages
	$db->query('alter table forum_replies rename to forum_messages');
	$db->query('alter table forum_replies_id_seq rename to forum_messages_id_seq');
	$db->query('alter table forum_messages alter column id set default nextval(\'forum_messages_id_seq\'::regclass)');
	
	ok();
?>
	
