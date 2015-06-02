#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';
require_once 'include/email.php';

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
	$email = trim($email);

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

	$members = $committee->get_members();

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
	$message = preg_replace(
		'/^Subject: (?!\[' . preg_quote($lijst->get('tag')) . '\])(.+?)$/im',
		'Subject: [' . $lijst->get('tag') . '] $1',
		$message, 1);

	// Find everyone who is subscribed to that list
	$aanmeldingen = $mailinglijsten_model->get_aanmeldingen($lijst);

	switch ($lijst->get('toegang'))
	{
		// Everyone can send mail to this list
		case DataModelMailinglijst::TOEGANG_IEDEREEN:
			// No problem, you can mail
			break;

		// Only people on the list can send mail to the list
		case DataModelMailinglijst::TOEGANG_DEELNEMERS:
			foreach ($aanmeldingen as $aanmelding)
				if (strcasecmp($aanmelding->get('email'), $from) === 0)
					break 2;

			// Also test whether the owner is sending mail, he should also be accepted.
			$commissie_model = get_model('DataModelCommissie');
			$commissie_adres = $commissie_model->get_email($lijst->get('commissie'));
			if (strcasecmp($from, $commissie_adres) === 0)
				break;
			
			return RETURN_NOT_ALLOWED_NOT_SUBSCRIBED;

		// Only people who sent mail from an *@svcover.nl address can send to the list
		case DataModelMailinglijst::TOEGANG_COVER:
			if (!preg_match('/\@svcover.nl$/i', $from))
				return RETURN_NOT_ALLOWED_NOT_COVER;

			break;

		// Only the owning committee can send mail to this list.
		case DataModelMailinglijst::TOEGANG_EIGENAAR:
			$commissie_model = get_model('DataModelCommissie');
			$commissie_adres = $commissie_model->get_email($lijst->get('commissie'));
			if (strcasecmp($from, $commissie_adres) !== 0)
				return RETURN_NOT_ALLOWED_NOT_OWNER;

			break;

		default:
			return RETURN_NOT_ALLOWED_UNKNOWN_POLICY;
	}

	if ($lijst->sends_email_on_first_email() && !$lijst->archive()->contains_email_from($from))
		send_welcome_mail($lijst, $from);

	foreach ($aanmeldingen as $aanmelding)
	{
		// Skip subscriptions without an e-mail address silently
		if (trim($aanmelding->get('email')) == '')
			continue;

		echo "Sending mail to " . $aanmelding->get('naam') . " <" . $aanmelding->get('email') . ">: ";

		// Personize the message for the receiver
		$variables = array(
			'[NAAM]' => htmlspecialchars($aanmelding->get('naam'), ENT_COMPAT, WEBSITE_ENCODING),
			'[NAME]' => htmlspecialchars($aanmelding->get('naam'), ENT_COMPAT, WEBSITE_ENCODING),
			'[MAILINGLIST]' => htmlspecialchars($lijst->get('naam'), ENT_COMPAT, WEBSITE_ENCODING)
		);

		if ($aanmelding->has('lid_id'))
			$variables['[LID_ID]'] = $aanmelding->get('lid_id');

		// If you are allowed to unsubscribe, parse the placeholder correctly (different for opt-in and opt-out lists)
		if ($lijst->get('publiek'))
		{
			$url = $lijst->get('type')== DataModelMailinglijst::TYPE_OPT_IN
				? ROOT_DIR_URI . sprintf('mailinglijsten.php?abonnement_id=%s', $aanmelding->get('abonnement_id'))
				: ROOT_DIR_URI . sprintf('mailinglijsten.php?lijst_id=%d', $lijst->get('id'));

			$variables['[UNSUBSCRIBE_URL]'] = htmlspecialchars($url, ENT_QUOTES, WEBSITE_ENCODING);

			$variables['[UNSUBSCRIBE]'] = sprintf('<a href="%s">Click here to unsubscribe from the %s mailinglist.</a>',
				htmlspecialchars($url, ENT_QUOTES, WEBSITE_ENCODING),
				htmlspecialchars($lijst->get('naam'), ENT_COMPAT, WEBSITE_ENCODING));
		}

		$personalized_message = str_replace(array_keys($variables), array_values($variables), $message);

		echo send_message($personalized_message, $aanmelding->get('email')), "\n";
	}

	return 0;
}

function process_return_to_sender($message, $return_code)
{
	if (!preg_match('/^From: (.+?)$/m', $message, $match) || !$from = parse_email_address($match[1]))
		return RETURN_COULD_NOT_DETERMINE_SENDER;

	if (!preg_match('/^Envelope-to: (.+?)$/m', $message, $match) || !$to = parse_email_address($match[1]))
		$to = null;

	$notice = 'Sorry, but your message' . ($to ? ' to ' . $to : '') . " could not be delivered:\n" . get_error_message($return_code);

	echo "Return message to sender $from\n";

	$message_part = \Cover\email\MessagePart::parse_text($message);

	$reply = \Cover\email\reply($message_part, $notice);

	$reply->setHeader('Subject', 'Message could not be delivered: ' . $message_part->header('Subject'));
	$reply->setHeader('From', 'Cover Mail Monkey <monkies@svcover.nl>');
	$reply->setHeader('Reply-To', 'Cover WebCie <webcie@ai.rug.nl>');

	return send_message($reply->toString(), $from);
}

function send_welcome_mail(DataIterMailinglijst $lijst, $to)
{
	$message = new \Cover\email\MessagePart();

	$message->setHeader('To', $to);
	$message->setHeader('From', 'Cover Mail Monkey <monkies@svcover.nl>');
	$message->setHeader('Reply-To', 'Cover WebCie <webcie@ai.rug.nl>');
	$message->setHeader('Subject', $lijst->get('on_first_email_subject'));
	$message->addBody('text/plain', strip_tags($lijst->get('on_first_email_message')));
	$message->addBody('text/html', $lijst->get('on_first_email_message'));

	return send_message($message->toString(), $to);
}

function send_message($message, $email)
{
	// Set up the proper pipes and thingies for the sendmail call;
	$descriptors = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("file", "php://stderr", "w"),  // stdout is a pipe that the child will write to
		2 => array("file", "php://stderr", "a")   // stderr is a file to write to
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

	return proc_close($sendmail);
}

function get_error_message($return_value)
{
	switch ($return_value)
	{
		case RETURN_COULD_NOT_DETERMINE_SENDER:
			return "Error: Could not determine sender.";

		case RETURN_COULD_NOT_DETERMINE_DESTINATION:
			return "Error: Could not determine destination.";

		case RETURN_COULD_NOT_DETERMINE_LIST:
			return "Error: Could not determine mailing list.";

		case RETURN_NOT_ALLOWED_NOT_SUBSCRIBED:
			return "Not allowed: Sender not subscribed to list.";

		case RETURN_NOT_ALLOWED_NOT_COVER:
			return "Not allowed: Sender does not match *@svcover.nl.";

		case RETURN_NOT_ALLOWED_NOT_OWNER:
			return "Not allowed: Sender not the owner of the list.";

		case RETURN_NOT_ALLOWED_UNKNOWN_POLICY:
			return "Not allowed: Unknown list policy.";

		case RETURN_FAILURE_MESSAGE_EMPTY:
			return "Error: Message empty.";

		default:
			return "(code $return_value)";
	}
}

function verbose($return_value)
{
	if ($return_value !== 0)
		echo get_error_message($return_value);

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

	// Parse the from header of the message archive
	if (!preg_match('/^From: (.+?)$/m', $message, $match) || !$from = parse_email_address($match[1]))
		$from = null;

	// Archive the message.
	$archief = get_model('DataModelMailinglijstArchief');
	$archief->archive($message, $from, $lijst, $commissie, $return_code);

	if ($return_code != 0)
		process_return_to_sender($message, $return_code);

	// Return the result of the processing step.
	return $return_code;
}

exit(verbose(main()));
