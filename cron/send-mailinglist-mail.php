#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';

error_reporting(E_ALL);
ini_set('display_errors', true);

$mailinglijsten_model = get_model('DataModelMailinglijst');

// Read the complete email from the stdin
$message = file_get_contents('php://stdin');

// Append '[Cover]' to the subject
$message = preg_replace('/^Subject: ((+?)$/m', 'Subject: [Cover] $1', $message, 1);

// Search for the adressed mailing list
if (!preg_match('/^Envelope-to: (.+?)$/m', $message, $match)) {
	echo "Kan envelope-to header niet vinden.\n";
	return -2;
}

// Find that mailing list
if (!($lijst = $mailinglijsten_model->get_lijst($match[1]))) {
	echo "Lijst {$match[1]} niet gevonden\n";
	return -3;
}

// Find everyone who is subscribed to that list
$aanmeldingen = $mailinglijsten_model->get_aanmeldingen($lijst->get('id'));

foreach ($aanmeldingen as $aanmelding)
{
	echo "Sending mail to " . $aanmelding->get('naam') . " <" . $aanmelding->get('email') . ">: ";

	// Personize the message for the receiver
	$variables = array(
		'[NAAM]' => $aanmelding->get('naam'),
		'[ABONNEMENT_ID]' => $aanmelding->get('abonnement_id')
	);

	$personalized_message = str_replace(array_keys($variables), array_values($variables), $message);

	// Set up the proper pipes and thingies for the sendmail call;
	$descriptors = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "a")   // stderr is a file to write to
	);

	$cwd = '/';

	$env = array();

	// Start sendmail with the target email address as argument
	$sendmail = proc_open(
		getenv('SENDMAIL') . ' -oi ' . escapeshellarg($aanmelding->get('email')),
		$descriptors, $pipes, $cwd, $env);

	// Write message to the stdin of sendmail
	fwrite($pipes[0], $personalized_message);
	fclose($pipes[0]);

	// Read the stdout
	// echo "  out: " . stream_get_contents($pipes[1]) . "\n";
	fclose($pipes[1]);

	// Read the stderr 
	// echo "  err: " . stream_get_contents($pipes[2]) . "\n";
	fclose($pipes[2]);

	echo proc_close($sendmail);
	echo "\n";
}

return 0;