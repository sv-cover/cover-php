#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'src/init.php';

$agenda_model = get_model('DataModelAgenda');

$from = new DateTime('-1 day');

$till = new DateTime();

$agenda_items = $agenda_model->get($from, $till, true);

foreach ($agenda_items as $agenda_item)
{
	// Skip external activities
	if ($agenda_item['extern'])
		continue;

	$email_address = $agenda_item['committee']['email'];

	$data = array('commissie_naam' => $agenda_item['committee']['naam']);

	$email = parse_email('ask_attendance.txt',
		array_merge($agenda_item->data, $data));

	$subject = sprintf("Attendance of '%s'", $agenda_item['kop']);

	$headers = array(
		'From: webcie@ai.rug.nl',
		'Reply-to: intern@svcover.nl');

	mail($email_address, $subject, $email, implode("\r\n", $headers));
}