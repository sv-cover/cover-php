#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace Cover\email\mailinglist;

require_once 'include/init.php';
require_once 'include/email.php';

use \Cover\email\MessagePart;
use \Cover\email\PeakableStream;

define('RETURN_COULD_NOT_DETERMINE_SENDER', 101);
define('RETURN_COULD_NOT_DETERMINE_DESTINATION', 102);
define('RETURN_COULD_NOT_DETERMINE_LIST', 103);
define('RETURN_COULD_NOT_DETERMINE_COMMITTEE', 104);
define('RETURN_COULD_NOT_PARSE_MESSAGE', 105);

define('RETURN_NOT_ADDRESSED_TO_COMMITTEE_MAILINGLIST', 201);

define('RETURN_NOT_ALLOWED_NOT_SUBSCRIBED', 401);
define('RETURN_NOT_ALLOWED_NOT_COVER', 402);
define('RETURN_NOT_ALLOWED_NOT_OWNER', 403);
define('RETURN_NOT_ALLOWED_UNKNOWN_POLICY', 404);

define('RETURN_FAILURE_MESSAGE_EMPTY', 502);
define('RETURN_MARKED_AS_SPAM', 503);
define('RETURN_MAIL_LOOP_DETECTED', 504);

error_reporting(E_ALL);
ini_set('display_errors', '1');

function parse_email_address(string $email)
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
		return null;
}

function parse_email_addresses(string $emails): array
{
	return array_filter(array_map('parse_email_address', explode(',', $emails)));
}

/**
 * Sends mail to committees@svcover.nl and workinggroups@svcover.nl to all
 * committees or all working groups. [COMMISSIE] and [COMMITTEE] in the
 * plain message will be replaced with the name of the committee. 
 * 
 * @param $message the raw message body
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
function process_message_to_all_committees(MessagePart $message, string $to, string $from): int
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

	if (in_array($loop_id, $message->headers('X-Loop')))
		return RETURN_MAIL_LOOP_DETECTED;

	$message->addHeader('X-Loop', $loop_id);

	$committees = $committee_model->get($destinations[$to]); // Get all committees of that type, not including hidden committees (such as board)

	foreach ($committees as $committee)
	{
		$email = $committee['login'] . '@svcover.nl';

		echo "Sending mail to " . $committee['naam'] . " <" . $email . ">: ";
		
		$variables = array(
			'[COMMISSIE]' => $committee['naam'],
			'[COMMITTEE]' => $committee['naam']
		);

		$personalized_message = \Cover\email\personalize($message, function($text) use ($variables) {
			return str_ireplace(array_keys($variables), array_values($variables), $text);
		});

		echo send_message($personalized_message, $email), "\n";
	}

	return 0;
}

function process_message_to_committee(MessagePart $message, string $to, DataIterCommissie &$committee = null): int
{
	$commissie_model = get_model('DataModelCommissie');

	// Find that committee
	if (!($committee = $commissie_model->get_from_email($to)))
		return RETURN_COULD_NOT_DETERMINE_COMMITTEE;

	$loop_id = sprintf('committee-%d', $committee['id']);

	if (in_array($loop_id, $message->headers('X-Loop')))
		return RETURN_MAIL_LOOP_DETECTED;

	$message->addHeader('X-Loop', $loop_id);

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

		$personalized_message = \Cover\email\personalize($message, function($text) use ($variables) {
			return str_ireplace(array_keys($variables), array_values($variables), $text);
		});

		echo send_message($personalized_message, $member['email']), "\n";
	}

	return 0;
}

function process_message_to_mailinglist(MessagePart $message, string $to, string $from, DataIterMailinglist &$lijst = null): int
{
	$mailinglijsten_model = get_model('DataModelMailinglist');

	// Find that mailing list
	if (!($lijst = $mailinglijsten_model->get_iter_by_address($to)))
		return RETURN_COULD_NOT_DETERMINE_LIST;

	$loop_id = sprintf('mailinglist-%d', $lijst['id']);

	if (in_array($loop_id, $message->headers('X-Loop')))
		return RETURN_MAIL_LOOP_DETECTED;

	$message->addHeader('X-Loop', $loop_id);

	// Append '[Cover]' or whatever tag is defined for this list to the subject
	// but do so only if it is set.
	if (!empty($lijst['tag']))
		$message->setHeader('Subject', preg_replace(
			'/^(?!(?:Re:\s*)?\[' . preg_quote($lijst['tag'], '/') . '\])(.+?)$/im',
			'[' . $lijst['tag'] . '] $1',
			$message->header('Subject'), 1));

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
			if (in_array($from, $lijst['committee']['email_addresses']))
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
			if (!in_array($from, $lijst['committee']['email_addresses']))
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

		$unsubscribe_url = ROOT_DIR_URI . sprintf('mailinglijsten.php?abonnement_id=%s', urlencode($aanmelding['abonnement_id']));
		$archive_url = ROOT_DIR_URI . sprintf('mailinglijsten.php?view=archive_index&id=%d', $lijst['id']);

		// Personize the message for the receiver
		$personalized_message = \Cover\email\personalize($message, function($text, $content_type) use ($aanmelding, $lijst, $unsubscribe_url, $archive_url) {
			$use_html = $content_type !== null && preg_match('/^text\/html/', $content_type);

			// Escape function depends on content type (text/html is treated differently)
			$escape = $use_html
				? function($text, $entities = ENT_COMPAT) { return htmlspecialchars($text, $entities, 'utf-8'); }
				: function($text, $entities = null) { return $text; };

			$variables = array(
				'[NAAM]' => $escape($aanmelding['naam']),
				'[NAME]' => $escape($aanmelding['naam']),
				'[MAILINGLIST]' => $escape($lijst['naam'])
			);

			if ($aanmelding['lid_id'])
				$variables['[LID_ID]'] = $aanmelding['lid_id'];

			$variables['[UNSUBSCRIBE_URL]'] = $escape($unsubscribe_url, ENT_QUOTES);

			$variables['[UNSUBSCRIBE]'] = sprintf(($use_html
				? '<a href="%s">Click here to unsubscribe from the %s mailinglist.</a>'
				: 'To unsubscribe from the %2$s mailinglist, go to %1$s'),
					$escape($unsubscribe_url),
					$escape($lijst['naam']));

			// Add an unsubscribe link to the footer when there isn't already a link in there, and
			// if users can unsubscribe from the list (i.e. public lists)
			if ($content_type !== null
				&& $lijst['publiek']
				&& strpos($text, '[UNSUBSCRIBE]') === false
				&& strpos($text, '[UNSUBSCRIBE_URL]') === false)
				$text .= sprintf($use_html
					? "<div><hr style=\"border:0;border-top:1px solid #ccc\"><small>You are receiving this mail because you are subscribed to the %s mailinglist. [UNSUBSCRIBE]</small></div>"
					: "\n\n---\nYou are receiving this mail because you are subscribed to the %s mailinglist. [UNSUBSCRIBE]",
					$escape($lijst['naam']));

			return str_ireplace(array_keys($variables), array_values($variables), $text);
		});

		$personalized_message->setHeader('List-Unsubscribe', sprintf('<%s>', $unsubscribe_url));
		$personalized_message->setHeader('List-Archive', sprintf('<%s>', $archive_url));

		echo send_message($personalized_message, $aanmelding['email']), "\n";
	}

	return 0;
}

function process_return_to_sender(MessagePart $message, string $from, $destination, $return_code): int
{
	$notice = 'Sorry, but your message' . ($destination ? ' to ' . $destination : '') . " could not be delivered:\n" . get_error_message($return_code);

	echo "Return message to sender $from\n";

	$reply = \Cover\email\reply($message, $notice);

	$reply->setHeader('Subject', 'Message could not be delivered: ' . $message->header('Subject'));
	$reply->setHeader('From', 'Cover Mail Monkey <monkies@svcover.nl>');
	$reply->setHeader('Reply-To', 'AC/DCee Cover <webcie@rug.nl>');

	return send_message($reply, $from);
}

function send_welcome_mail(DataIterMailinglist $lijst, string $to): int
{
	$message = new \Cover\email\MessagePart();

	$message->setHeader('To', $to);
	$message->setHeader('From', sprintf('%s <%s>', $lijst['naam'], $lijst['adres']));
	$message->setHeader('Reply-To', 'AC/DCee Cover <webcie@rug.nl>');
	$message->setHeader('Subject', (string) $lijst['on_first_email_subject']);
	$message->addBody('text/plain', strip_tags($lijst['on_first_email_message']));
	$message->addBody('text/html', $lijst['on_first_email_message']);

	return send_message($message, $to);
}

function send_message(MessagePart $message, string $email): int
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
	fwrite($pipes[0], $message->toString());
	fclose($pipes[0]);

	return proc_close($sendmail);
}

function get_error_message(int $return_value): string
{
	switch ($return_value)
	{
		case RETURN_COULD_NOT_PARSE_MESSAGE:
			return "Error: Could not parse the message.";

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

function send_mailinglist_mail($buffer_stream): int
{
	try {
		// Read the complete email from the stdin.
		rewind($buffer_stream);
		$message = MessagePart::parse_stream(new PeakableStream($buffer_stream));
	} catch (Exception $e) {
		sentry_report_exception($e);
		return RETURN_COULD_NOT_PARSE_MESSAGE;
	}
	
	$lijst = null;
	$comissie = null;

	// Test at least the sender already
	if (!$message->header('From') || !$from = parse_email_address($message->header('From')))
		return RETURN_COULD_NOT_DETERMINE_SENDER;

	if (!$message->header('Envelope-To') || !$destinations = parse_email_addresses($message->header('Envelope-To')))
		return RETURN_COULD_NOT_DETERMINE_DESTINATION;

	if ($message->header('X-Spam-Flag') == 'YES')
		return RETURN_MARKED_AS_SPAM;

	$return_code = 0;

	foreach (array_unique($destinations) as $destination)
	{
		$commissie = null;

		// First try if this message is addressed to committees@svcover.nl
		$return_code = process_message_to_all_committees($message, $destination, $from);

		if ($return_code === RETURN_NOT_ADDRESSED_TO_COMMITTEE_MAILINGLIST)
		{
			// Then try sending the message to a committee
			$return_code = process_message_to_committee($message, $destination, $commissie);

			// If that didn't work, try sending it to a mailing list
			if ($return_code === RETURN_COULD_NOT_DETERMINE_COMMITTEE)
			{
				// Process the message: parse it and send it to the list.
				$return_code = process_message_to_mailinglist($message, $destination, $from, $lijst);
			}
		}

		// Archive the message.
		rewind($buffer_stream);
		$archief = get_model('DataModelMailinglistArchive');
		$archief->archive($buffer_stream, $from, $lijst, $commissie, $return_code);

		if ($return_code !== 0)
			process_return_to_sender($message, $from, $destination, $return_code);
	}

	// Return the result of the processing step.
	return $return_code;
}
