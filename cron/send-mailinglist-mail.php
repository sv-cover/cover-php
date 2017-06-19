#!/usr/bin/env php
<?php
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';
require_once 'include/email.php';

define('RETURN_COULD_NOT_DETERMINE_SENDER', 101);
define('RETURN_COULD_NOT_DETERMINE_DESTINATION', 102);
define('RETURN_COULD_NOT_DETERMINE_LIST', 103);
define('RETURN_COULD_NOT_DETERMINE_COMMITTEE', 104);
define('RETURN_COULD_NOT_PARSE_MESSAGE_HEADER', 105);

define('RETURN_NOT_ADDRESSED_TO_COMMITTEE_MAILINGLIST', 201);

define('RETURN_NOT_ALLOWED_NOT_SUBSCRIBED', 401);
define('RETURN_NOT_ALLOWED_NOT_COVER', 402);
define('RETURN_NOT_ALLOWED_NOT_OWNER', 403);
define('RETURN_NOT_ALLOWED_UNKNOWN_POLICY', 404);

define('RETURN_FAILURE_MESSAGE_EMPTY', 502);
define('RETURN_MARKED_AS_SPAM', 503);
define('RETURN_MAIL_LOOP_DETECTED', 504);

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

function parse_email_addresses($emails)
{
	return array_filter(array_map('parse_email_address', explode(',', $emails)));
}

/**
 * Sends mail to committees@svcover.nl and workinggroups@svcover.nl to all
 * committees or all working groups. [COMMISSIE] and [COMMITTEE] in the
 * plain message will be replaced with the name of the committee. 
 * 
 * @param $message the raw message body
 * @param $message_headers the message headers, parsed> Not actually used.
 * @param $to destination address, ideally committees@svcover.nl
 *            or workinggroups@svcover.nl.
 * @param $from the email address of the sender. Must end in @svcover.nl or
 *              the function will return RETURN_NOT_ALLOWED_NOT_COVER.
 * 
 * @return RETURN_NOT_ADDRESSED_TO_COMMITTEE_MAILINGLIST if the mail is not
 * addressed to one of those email addresses.
 * @return RETURN_NOT_ALLOWED_NOT_COVER if the mail was not sent from an
 * address ending in @svcover.nl.
 */
function process_message_to_all_committees($message, $message_headers, $to, $from)
{
	$committee_model = get_model('DataModelCommissie');

	// Strip svcover.nl domain from $to, if it is there.
	if (preg_match('/@svcover\.nl$/i', $to))
		$to = substr($to, 0, -strlen('@svcover.nl'));

	$to = strtolower($to); // case insensitive please

	$destinations = [
		'committees' => DataModelCommissie::TYPE_COMMITTEE,
		'workingroups' => DataModelCommissie::TYPE_WORKING_GROUP
	];

	// Validate whether it is actually addressed to the committee (or working group) mailing list
	if (!array_key_exists($to, $destinations))
		return RETURN_NOT_ADDRESSED_TO_COMMITTEE_MAILINGLIST;

	// Only @svcover.nl addresses can send to these mailing lists
	if (!preg_match('/@svcover\.nl$/i', $from))
		return RETURN_NOT_ALLOWED_NOT_COVER;

	$loop_id = sprintf('all-%s', $to);

	if (in_array($loop_id, $message_headers->headers('X-Loop')))
		return RETURN_MAIL_LOOP_DETECTED;

	$message = add_header($message, 'X-Loop: ' . $loop_id);

	$committees = $committee_model->get($destinations[$to]); // Get all committees of that type, not including hidden committees (such as board)

	foreach ($committees as $committee)
	{
		$email = $committee['login'] . '@svcover.nl';

		echo "Sending mail to " . $committee['naam'] . " <" . $email . ">: ";
		
		$variables = array(
			'[COMMISSIE]' => $committee['naam'],
			'[COMMITTEE]' => $committee['naam'],
			'[NAAM]' => $committee['naam'],
			'[NAME]' => $committee['naam']
		);

		$personalized_message = str_replace(array_keys($variables), array_values($variables), $message);

		echo send_message($personalized_message, $email), "\n";
	}

	return 0;
}

function process_message_to_committee($message, $message_headers, $to, &$committee)
{
	$commissie_model = get_model('DataModelCommissie');

	// Find that committee
	if (!($committee = $commissie_model->get_from_email($to)))
		return RETURN_COULD_NOT_DETERMINE_COMMITTEE;

	$loop_id = sprintf('committee-%d', $committee['id']);

	if (in_array($loop_id, $message_headers->headers('X-Loop')))
		return RETURN_MAIL_LOOP_DETECTED;

	$message = add_header($message, 'X-Loop: ' . $loop_id);

	$members = $committee->get_members();

	foreach ($members as $member)
	{
		echo "Sending mail to " . $member['voornaam'] . " <" . $member['email'] . ">: ";

		$variables = array(
			'[NAAM]' => $member['voornaam'],
			'[NAME]' => $member['voornaam'],
			'[COMMISSIE]' => $committee['naam'],
			'[COMMITTEE]' => $committee['naam']
		);

		$personalized_message = str_replace(array_keys($variables), array_values($variables), $message);

		echo send_message($personalized_message, $member['email']), "\n";
	}

	return 0;
}

function process_message_to_mailinglist($message, $message_header, $to, $from, &$lijst)
{
	$mailinglijsten_model = get_model('DataModelMailinglist');

	// Find that mailing list
	if (!($lijst = $mailinglijsten_model->get_iter_by_address($to)))
		return RETURN_COULD_NOT_DETERMINE_LIST;

	$loop_id = sprintf('mailinglist-%d', $lijst['id']);

	if (in_array($loop_id, $message_header->headers('X-Loop')))
		return RETURN_MAIL_LOOP_DETECTED;

	$message = add_header($message, 'X-Loop: ' . $loop_id);

	// Append '[Cover]' or whatever tag is defined for this list to the subject
	// but do so only if it is set.
	if (!empty($lijst['tag']))
		$message = preg_replace(
			'/^Subject: (?!(?:Re:\s*)?\[' . preg_quote($lijst['tag'], '/') . '\])(.+?)$/im',
			'Subject: [' . $lijst['tag'] . '] $1',
			$message, 1);

	// Find everyone who is subscribed to that list
	$aanmeldingen = $lijst['subscriptions'];

	switch ($lijst['toegang'])
	{
		// Everyone can send mail to this list
		case DataModelMailinglist::TOEGANG_IEDEREEN:
			// No problem, you can mail
			break;

		// Only people on the list can send mail to the list
		case DataModelMailinglist::TOEGANG_DEELNEMERS:
			foreach ($aanmeldingen as $aanmelding)
				if (strcasecmp($aanmelding['email'], $from) === 0)
					break 2;

			// Also test whether the owner is sending mail, he should also be accepted.
			if (strcasecmp($from, $lijst['committee']['email']) === 0)
				break;
			
			// Nope, access denied
			return RETURN_NOT_ALLOWED_NOT_SUBSCRIBED;

		// Only people who sent mail from an *@svcover.nl address can send to the list
		case DataModelMailinglist::TOEGANG_COVER:
			if (!preg_match('/\@svcover.nl$/i', $from))
				return RETURN_NOT_ALLOWED_NOT_COVER;

			break;

		// Only the owning committee can send mail to this list.
		case DataModelMailinglist::TOEGANG_EIGENAAR:
			if (strcasecmp($from, $lijst['committee']['email']) !== 0)
				return RETURN_NOT_ALLOWED_NOT_OWNER;

			break;

		default:
			return RETURN_NOT_ALLOWED_UNKNOWN_POLICY;
	}

	if ($lijst->sends_email_on_first_email() && !$lijst['archive']->contains_email_from($from))
		send_welcome_mail($lijst, $from);

	foreach ($aanmeldingen as $aanmelding)
	{
		// Skip subscriptions without an e-mail address silently
		if (trim($aanmelding['email']) == '')
			continue;

		echo "Sending mail to " . $aanmelding['naam'] . " <" . $aanmelding['email'] . ">: ";

		// Personize the message for the receiver
		$variables = array(
			'[NAAM]' => htmlspecialchars($aanmelding['naam'], ENT_COMPAT, 'UTF-8'),
			'[NAME]' => htmlspecialchars($aanmelding['naam'], ENT_COMPAT, 'UTF-8'),
			'[MAILINGLIST]' => htmlspecialchars($lijst['naam'], ENT_COMPAT, 'UTF-8')
		);

		if ($aanmelding['lid_id'])
			$variables['[LID_ID]'] = $aanmelding['lid_id'];

		// If you are allowed to unsubscribe, parse the placeholder correctly (different for opt-in and opt-out lists)
		if ($lijst['publiek'])
		{
			$url = ROOT_DIR_URI . sprintf('mailinglijsten.php?abonnement_id=%s', urlencode($aanmelding['abonnement_id']));

			$variables['[UNSUBSCRIBE_URL]'] = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

			$variables['[UNSUBSCRIBE]'] = sprintf('<a href="%s">Click here to unsubscribe from the %s mailinglist.</a>',
				htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($lijst['naam'], ENT_COMPAT, 'UTF-8'));
		}

		$personalized_message = str_replace(array_keys($variables), array_values($variables), $message);

		echo send_message($personalized_message, $aanmelding['email']), "\n";
	}

	return 0;
}

function process_return_to_sender($message, $message_header, $from, $destination, $return_code)
{
	$notice = 'Sorry, but your message' . ($destination ? ' to ' . $destination : '') . " could not be delivered:\n" . get_error_message($return_code);

	echo "Return message to sender $from\n";

	$message_part = \Cover\email\MessagePart::parse_text($message);

	$reply = \Cover\email\reply($message_part, $notice);

	$reply->setHeader('Subject', 'Message could not be delivered: ' . $message_part->header('Subject'));
	$reply->setHeader('From', 'Cover Mail Monkey <monkies@svcover.nl>');
	$reply->setHeader('Reply-To', 'AC/DCee Cover <webcie@rug.nl>');

	return send_message($reply->toString(), $from);
}

function send_welcome_mail(DataIterMailinglist $lijst, $to)
{
	$message = new \Cover\email\MessagePart();

	$message->setHeader('To', $to);
	$message->setHeader('From', sprintf('%s <%s>', $lijst['naam'], $lijst['adres']));
	$message->setHeader('Reply-To', 'AC/DCee Cover <webcie@rug.nl>');
	$message->setHeader('Subject', $lijst['on_first_email_subject']);
	$message->addBody('text/plain', strip_tags($lijst['on_first_email_message']));
	$message->addBody('text/html', $lijst['on_first_email_message']);

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
		case RETURN_COULD_NOT_PARSE_MESSAGE_HEADER:
			return "Error: Could not parse the message header.";

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

		case RETURN_MARKED_AS_SPAM:
			return "The message was marked as 'spammy' by the spamfilter.";

		case RETURN_NOT_ADDRESSED_TO_COMMITTEE_MAILINGLIST:
			return "The message is not addressed to the committee mailing list.";

		default:
			return "(code $return_value)";
	}
}

function read_message_headers($stream)
{
	$message_header = new \cover\email\MessagePart();

	$success = \cover\email\MessagePart::parse_header(
		new \cover\email\PeakableStream($stream),
		$message_header);

	if (!$success)
		return false;

	return $message_header;
}

function add_header($message, $header)
{
	// Just find the first \n\n occurrence, and prepend it there. Yes, sloppy.
	if (!preg_match('/\r?\n\r?\n/', $message, $match, PREG_OFFSET_CAPTURE))
		throw new RuntimeException('Cannot add header: cannot find the end of the message\'s header.');

	return substr($message, 0, $match[0][1]) . "\r\n" . $header . substr($message, $match[0][1]);
}

function verbose($return_value)
{
	if ($return_value !== 0)
		fwrite(STDERR, get_error_message($return_value) . "\n");

	return $return_value;
}

function main()
{
	// Copy STDIN to buffer stream because th
	$buffer_stream = fopen('php://temp', 'r+');
	stream_copy_to_stream(STDIN, $buffer_stream);

	// Read the complete email from the stdin.
	rewind($buffer_stream);
	$message = stream_get_contents($buffer_stream);
	
	$lijst = null;
	$comissie = null;

	if ($message === false || trim($message) == '')
		return RETURN_FAILURE_MESSAGE_EMPTY;

	// Rewind the STDIN but skip the first line
	rewind($buffer_stream);
	fgets($buffer_stream); // Skip the first line

	// Next, read the header of the mail again, but now using the message parser that
	// correctly handles headers with newlines (which are hell with regexps).
	$message_header = read_message_headers($buffer_stream);

	if ($message_header === false)
		return RETURN_COULD_NOT_PARSE_MESSAGE_HEADER;

	fclose($buffer_stream);

	// Test at least the sender already
	if (!$message_header->header('From') || !$from = parse_email_address($message_header->header('From')))
		return RETURN_COULD_NOT_DETERMINE_SENDER;

	if (!$message_header->header('Envelope-To') || !$destinations = parse_email_addresses($message_header->header('Envelope-To')))
		return RETURN_COULD_NOT_DETERMINE_DESTINATION;

	if ($message_header->header('X-Spam-Flag') == 'YES')
		return RETURN_MARKED_AS_SPAM;

	foreach ($destinations as $destination)
	{
		$commissie = null;

		// First try if this message is addressed to committees@svcover.nl
		$return_code = process_message_to_all_committees($message, $message_header, $destination, $from);

		if ($return_code === RETURN_NOT_ADDRESSED_TO_COMMITTEE_MAILINGLIST)
		{
			// Then try sending the message to a committee
			$return_code = process_message_to_committee($message, $message_header, $destination, $commissie);

			// If that didn't work, try sending it to a mailing list
			if ($return_code === RETURN_COULD_NOT_DETERMINE_COMMITTEE)
			{
				// Process the message: parse it and send it to the list.
				$return_code = process_message_to_mailinglist($message, $message_header, $destination, $from, $lijst);
			}
		}

		// Archive the message.
		$archief = get_model('DataModelMailinglistArchive');
		$archief->archive($message, $from, $lijst, $commissie, $return_code);

		if ($return_code !== 0)
			process_return_to_sender($message, $message_header, $from, $destination, $return_code);
	}

	// Return the result of the processing step.
	return $return_code;
}

exit(verbose(main()));
