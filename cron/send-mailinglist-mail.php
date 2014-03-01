#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';

define('RETURN_COULD_NOT_DETERMINE_SENDER', 101);
define('RETURN_COULD_NOT_DETERMINE_DESTINATION', 102);
define('RETURN_COULD_NOT_DETERMINE_LIST', 103);
define('RETURN_COULD_NOT_DETERMINE_COMMITTEE', 104);
define('RETURN_NOT_ALLOWED_NOT_SUBSCRIBED', 401);
define('RETURN_NOT_ALLOWED_NOT_COVER', 402);
define('RETURN_NOT_ALLOWED_NOT_OWNER', 403);
define('RETURN_NOT_ALLOWED_UNKNOWN_POLICY', 404);
define('RETURN_FAILURE_MESSAGE_EMPTY', 502);

error_reporting(E_ALL);
ini_set('display_errors', true);

function parse_email_address($email)
{
	// 'jelmer@ikhoefgeen.nl'
	if (filter_var($email, FILTER_VALIDATE_EMAIL))
		return $email;

	// Jelmer van der Linde <jelmer@ikhoefgeen.nl>
	else if (preg_match('/<(.+?)>$/', trim($email), $match)
		&& filter_var($match[1], FILTER_VALIDATE_EMAIL))
		return $match[1];

	else
		return false;
}

function process_message_committee($message, &$committee)
{
	$commissie_model = get_model('DataModelCommissie');

	// Search who send it
	if (!preg_match('/^From: (.+?)$/m', $message, $match) || !$from = parse_email_address($match[1]))
		return RETURN_COULD_NOT_DETERMINE_SENDER;

	// Search for the adressed committee
	if (!preg_match('/^Envelope-to: (.+?)$/m', $message, $match) || !$to = parse_email_address($match[1]))
		return RETURN_COULD_NOT_DETERMINE_DESTINATION;

	// Find that committee
	if (!($committee = $commissie_model->get_from_email($to)))
		return RETURN_COULD_NOT_DETERMINE_COMMITTEE;

	$members = $commissie_model->get_leden($committee->get('id'));

	foreach ($members as $member)
	{
		echo "Sending mail to " . $member->get('voornaam') . " <" . $member->get('email') . ">: ";

		$variables = array(
			'[NAAM]' => $member->get('voornaam'),
			'[NAME]' => $member->get('voornaam'),
			'[COMMISSIE]' => $committee->get('naam'),
			'[COMMITTEE]' => $committee->get('naam')
		);

		$personalized_message = str_replace(array_keys($variables), array_values($variables), $message);

		echo send_message($personalized_message, $member->get('email')), "\n";
	}

	return 0;
}

function process_message_mailinglist($message, &$lijst)
{
	$mailinglijsten_model = get_model('DataModelMailinglijst');

	// Search who send it
	if (!preg_match('/^From: (.+?)$/m', $message, $match) || !$from = parse_email_address($match[1]))
		return RETURN_COULD_NOT_DETERMINE_SENDER;

	// Search for the adressed mailing list
	if (!preg_match('/^Envelope-to: (.+?)$/m', $message, $match) || !$to = parse_email_address($match[1]))
		return RETURN_COULD_NOT_DETERMINE_DESTINATION;

	// Find that mailing list
	if (!($lijst = $mailinglijsten_model->get_lijst($to)))
		return RETURN_COULD_NOT_DETERMINE_LIST;

	// Append '[Cover]' to the subject
	$message = preg_replace('/^Subject: (.+?)$/m', 'Subject: [Cover] $1', $message, 1);

	// Find everyone who is subscribed to that list
	$aanmeldingen = $mailinglijsten_model->get_aanmeldingen($lijst->get('id'));

	switch ($lijst->get('toegang'))
	{
		// Everyone can send mail to this list
		case DataModelMailinglijst::TOEGANG_IEDEREEN:
			// No problem, you can mail
			break;

		// Only people on the list can send mail to the list
		case DataModelMailinglijst::TOEGANG_DEELNEMERS:
			foreach ($aanmeldingen as $aanmelding)
				if ($aanmelding->get('email') == $from)
					break 2;

			// Also test whether the owner is sending mail, he should also be accepted.
			$commissie_model = get_model('DataModelCommissie');
			$commissie_adres = $commissie_model->get_email($lijst->get('commissie'));
			if ($from == $commissie_adres)
				break;
			
			return RETURN_NOT_ALLOWED_NOT_SUBSCRIBED;

		// Only people who sent mail from an *@svcover.nl address can send to the list
		case DataModelMailinglijst::TOEGANG_COVER:
			if (!preg_match('/\@svcover.nl$/', $from))
				return RETURN_NOT_ALLOWED_NOT_COVER;

			break;

		// Only the owning committee can send mail to this list.
		case DataModelMailinglijst::TOEGANG_EIGENAAR:
			$commissie_model = get_model('DataModelCommissie');
			$commissie_adres = $commissie_model->get_email($lijst->get('commissie'));
			if ($from != $commissie_adres)
				return RETURN_NOT_ALLOWED_NOT_OWNER;

			break;

		default:
			return RETURN_NOT_ALLOWED_UNKNOWN_POLICY;
	}

	foreach ($aanmeldingen as $aanmelding)
	{
		echo "Sending mail to " . $aanmelding->get('naam') . " <" . $aanmelding->get('email') . ">: ";

		// Personize the message for the receiver
		$variables = array(
			'[NAAM]' => $aanmelding->get('naam'),
			'[NAME]' => $aanmelding->get('naam'),
			'[ABONNEMENT_ID]' => $aanmelding->get('abonnement_id'),
			'[UNSUBSCRIBE]' => sprintf('<a href="https://svcover.nl/mailinglijsten.php?abonnement_id=%s">Click here to unsubscribe from the %s mailinglist.</a>',
				$aanmelding->get('abonnement_id'), htmlspecialchars($lijst->get('naam')))
		);

		$personalized_message = str_replace(array_keys($variables), array_values($variables), $message);

		echo send_message($personalized_message, $aanmelding->get('email')), "\n";
	}

	return 0;
}

function send_message($message, $email)
{
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
		getenv('SENDMAIL') . ' -oi ' . escapeshellarg($email),
		$descriptors, $pipes, $cwd, $env);

	// Write message to the stdin of sendmail
	fwrite($pipes[0], $message);
	fclose($pipes[0]);

	// Read the stdout
	// echo "  out: " . stream_get_contents($pipes[1]) . "\n";
	fclose($pipes[1]);

	// Read the stderr 
	// echo "  err: " . stream_get_contents($pipes[2]) . "\n";
	fclose($pipes[2]);

	return proc_close($sendmail);
}

function verbose($return_value)
{
	switch ($return_value)
	{
		case RETURN_COULD_NOT_DETERMINE_SENDER:
			echo "Error: Could not determine sender.";
			break;

		case RETURN_COULD_NOT_DETERMINE_DESTINATION:
			echo "Error: Could not determine destination.";
			break;

		case RETURN_COULD_NOT_DETERMINE_LIST:
			echo "Error: Could not determine mailing list.";
			break;

		case RETURN_NOT_ALLOWED_NOT_SUBSCRIBED:
			echo "Not allowed: Sender not subscribed to list.";
			break;

		case RETURN_NOT_ALLOWED_NOT_COVER:
			echo "Not allowed: Sender does not match *@svcover.nl.";
			break;

		case RETURN_NOT_ALLOWED_NOT_OWNER:
			echo "Not allowed: Sender not the owner of the list.";
			break;

		case RETURN_NOT_ALLOWED_UNKNOWN_POLICY:
			echo "Not allowed: Unknown list policy.";
			break;

		case RETURN_FAILURE_MESSAGE_EMPTY:
			echo "Error: Message empty.";
			break;
	}

	if ($return_value !== 0)
		echo "(code $return_value)\n";

	return $return_value;
}

function main()
{
	// Read the complete email from the stdin.
	$message = file_get_contents('php://stdin');

	$lijst = null;
	$comissie = null;

	if ($message === false || trim($message) == '')
		return RETURN_FAILURE_MESSAGE_EMPTY;

	// First try sending the message to a committee
	$return_code = process_message_committee($message, $commissie);

	// If that didn't work, try sending it to a mailing list
	if ($return_code == RETURN_COULD_NOT_DETERMINE_COMMITTEE)
	{
		// Process the message: parse it and send it to the list.
		$return_code = process_message_mailinglist($message, $lijst);
	}

	// Archive the message.
	$archief = get_model('DataModelMailinglijstArchief');
	$archief->archive($message, $lijst, $commissie, $return_code);

	// Return the result of the processing step.
	return $return_code;
}

exit(verbose(main()));