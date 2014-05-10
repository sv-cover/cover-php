#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';

$agenda_model = get_model('DataModelAgenda');

$commissie_model = get_model('DataModelCommissie');

$from = new DateTime('-1 day');

$till = new DateTime();

$agenda_items = $agenda_model->get($from->format('Y-m-d'), $till->format('Y-m-d'), true);

foreach ($agenda_items as $agenda_item)
{
	// Skip external activities
	if ($agenda_item->get('extern'))
		continue;

	$email_address = $commissie_model->get_email($agenda_item->get('commissie'));

	$data = array('commissie_naam' => $commissie_model->get_naam($agenda_item->get('commissie')));

	$email = parse_email('ask_attendance.txt',
		array_merge($agenda_item->data, $data));

	$subject = sprintf("Attendance of '%s'", $agenda_item->get('kop'));

	$headers = array(
		'From: webcie@ai.rug.nl',
		'Reply-to: intern@svcover.nl');

	mail($email_address, $subject, $email, implode("\r\n", $headers));
}