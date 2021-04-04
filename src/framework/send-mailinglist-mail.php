<?php
declare(strict_types=1);

namespace Cover\email\mailinglist;

require_once 'src/init.php';
require_once 'src/framework/email.php';

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
	return array_filter(array_map(__NAMESPACE__ . '\parse_email_address', explode(',', $emails)));
}

function validate_message_to_all_committees(MessagePart $message, string &$to, string $from, array &$destinations=null, string &$loop_id=null)
{
	$committee_model = get_model('DataModelCommissie');

	// Strip svcover.nl domain from $to, if it is there.
	if (preg_match('/@svcover\.nl$/i', $to))
		$to = substr($to, 0, -strlen('@svcover.nl'));

	$to = strtolower($to); // case insensitive please

	$destinations = [
		'committees' => $committee_model::TYPE_COMMITTEE,
		'workingroups' => $committee_model::TYPE_WORKING_GROUP
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

	return 0;
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
	$result = validate_message_to_all_committees($message, $to, $from);

	if ($result === 0)
	{
		$queue = get_model('DataModelMailinglistQueue');
		$queue->queue($message->toString(), $to, 'all_committees');
	}

	return $result;
}

function validate_message_to_committee(MessagePart $message, string $to, \DataIterCommissie &$committee=null, string &$loop_id=null)
{
	$commissie_model = get_model('DataModelCommissie');

	// Find that committee
	if (!$committee)
		$committee = $commissie_model->get_from_email($to);

	// Error if still no committee
	if (!$committee)
		return RETURN_COULD_NOT_DETERMINE_COMMITTEE;

	$loop_id = sprintf('committee-%d', $committee['id']);

	if (in_array($loop_id, $message->headers('X-Loop')))
		return RETURN_MAIL_LOOP_DETECTED;

	return 0;
}

function process_message_to_committee(MessagePart $message, string $to, \DataIterCommissie &$committee=null): int
{
	$result = validate_message_to_committee($message, $to, $committee);

	if ($result === 0)
	{
		$queue = get_model('DataModelMailinglistQueue');
		$queue->queue($message->toString(), $to, 'committee');
	}

	return $result;
}


function validate_message_to_mailinglist(MessagePart $message, string $to, string $from, \DataIterMailinglist &$list=null, array &$subscriptions=null, string &$loop_id=null)
{
	$mailinglist_model = get_model('DataModelMailinglist');

	// Find that mailing list
	if (!$list)
		$list = $mailinglist_model->get_iter_by_address($to);

	// Error if still no list
	if (!$list)
		return RETURN_COULD_NOT_DETERMINE_LIST;

	$loop_id = sprintf('mailinglist-%d', $list['id']);

	if (in_array($loop_id, $message->headers('X-Loop')))
		return RETURN_MAIL_LOOP_DETECTED;

	// Find everyone who is subscribed to that list
	$subscriptions = $list['subscriptions'];

	switch ($list['toegang'])
	{
		// Everyone can send mail to this list
		case $mailinglist_model::TOEGANG_IEDEREEN:
			// No problem, you can mail
			break;

		// Only people on the list can send mail to the list
		case $mailinglist_model::TOEGANG_DEELNEMERS:
			foreach ($subscriptions as $aanmelding)
				if (strcasecmp($aanmelding['email'], $from) === 0)
					break 2;

			// Also test whether the owner is sending mail, he should also be accepted.
			if (in_array($from, $list['committee']['email_addresses']))
				break;
			
			// Nope, access denied
			return RETURN_NOT_ALLOWED_NOT_SUBSCRIBED;

		// Only people who sent mail from an *@svcover.nl address can send to the list
		case $mailinglist_model::TOEGANG_COVER:
			if (!preg_match('/\@svcover.nl$/i', $from))
				return RETURN_NOT_ALLOWED_NOT_COVER;
			break;

		// Only the owning committee can send mail to this list.
		case $mailinglist_model::TOEGANG_EIGENAAR:
			if (!in_array($from, $list['committee']['email_addresses']))
				return RETURN_NOT_ALLOWED_NOT_OWNER;
			break;

		default:
			return RETURN_NOT_ALLOWED_UNKNOWN_POLICY;
	}

	return 0;
}

function process_message_to_mailinglist(MessagePart $message, string $to, string $from, \DataIterMailinglist &$list = null): int
{
	$list = null;
	$result = validate_message_to_mailinglist($message, $to, $from, $list);

	if ($result === 0)
	{
		$queue = get_model('DataModelMailinglistQueue');
		$queue->queue($message->toString(), $to, 'mailinglist', $list);
	}

	return $result;
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

function send_welcome_mail(\DataIterMailinglist $list, string $to): int
{
	$message = new \Cover\email\MessagePart();

	$message->setHeader('To', $to);
	$message->setHeader('From', sprintf('%s <%s>', $list['naam'], $list['adres']));
	$message->setHeader('Reply-To', 'AC/DCee Cover <webcie@rug.nl>');
	$message->setHeader('Subject', (string) $list['on_first_email_subject']);
	$message->addBody('text/plain', strip_tags($list['on_first_email_message']));
	$message->addBody('text/html', $list['on_first_email_message']);

	return send_message($message, $to);
}

function send_message(MessagePart $message, string $email): int
{
	$message->addHeader('X-Mailing-List-Destination', $email);

	// Set up the proper pipes and thingies for the sendmail call;
	$descriptors = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("file", "php://stderr", "a")   // stderr is a file to write to
	);

	$cwd = '/';

	$env = array();

	if (getenv('SENDMAIL'))
		$sendmail_path = getenv('SENDMAIL');
	else
		// Strip args from sendmail path in ini
		list($sendmail_path) = explode(' ', ini_get('sendmail_path'));

	// Start sendmail with the target email address as argument
	$sendmail = proc_open(
		$sendmail_path . ' -oi ' . escapeshellarg($email),
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
	
	$list = null;
	$committee = null;

	// Test at least the sender already
	if (!$message->header('From') || !$from = parse_email_address($message->header('From')))
		return RETURN_COULD_NOT_DETERMINE_SENDER;

	$destinations_header = $message->header('Envelope-To') ?? $message->header('X-Mailing-List-Destination') ?? $message->header('To');
	if (!$destinations = parse_email_addresses($destinations_header))
		return RETURN_COULD_NOT_DETERMINE_DESTINATION;

	if ($message->header('X-Spam-Flag') == 'YES')
		return RETURN_MARKED_AS_SPAM;

	$return_code = 0;

	foreach (array_unique($destinations) as $destination)
	{
		$committee = null;

		// First try if this message is addressed to committees@svcover.nl
		$return_code = process_message_to_all_committees($message, $destination, $from);

		if ($return_code === RETURN_NOT_ADDRESSED_TO_COMMITTEE_MAILINGLIST)
		{
			// Then try sending the message to a committee
			$return_code = process_message_to_committee($message, $destination, $committee);

			// If that didn't work, try sending it to a mailing list
			if ($return_code === RETURN_COULD_NOT_DETERMINE_COMMITTEE)
			{
				// Process the message: parse it and send it to the list.
				$return_code = process_message_to_mailinglist($message, $destination, $from, $list);
			}
		}

		// Archive the message.
		rewind($buffer_stream);
		$archief = get_model('DataModelMailinglistArchive');
		$archief->archive($buffer_stream, $from, $list, $committee, $return_code);

		if ($return_code !== 0)
			process_return_to_sender($message, $from, $destination, $return_code);
	}

	// Return the result of the processing step.
	return $return_code;
}
