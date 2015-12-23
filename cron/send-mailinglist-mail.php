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

class EnvelopeTo extends \Zend\Mail\Header\AbstractAddressList
{
    protected $fieldName = 'Envelope-To';
    protected static $type = 'envelope-to';
}

class Link
{
	public $url;

	public $label;

	public function __construct($url, $label)
	{
		$this->url = $url;

		$this->label = $label;
	}

	public function toText($charset)
	{
		return sprintf("%s (%s)", convert_utf8_to_encoding($this->label, $charset), $this->url);
	}

	public function toHTML($charset)
	{
		return sprintf('<a href="%s">%s</a>',
			htmlentities($this->url, ENT_QUOTES, $charset),
			htmlentities(convert_utf8_to_encoding($this->label, $charset), ENT_COMPAT, $charset));
	}
}

function convert_utf8_to_encoding($text, $charset)
{
	return $text; // TODO
}

function parse_message($raw_message)
{
	$message = \Zend\Mail\Message::fromString(substr($raw_message, strpos($raw_message, "\n") + 1));

	if (preg_match('/^multipart\/alternative;\s*boundary=(["\']?)(.+?)\1$/',
		$message->getHeaders()->get('contenttype')->getFieldValue(), $match))
	{
		$boundary = $match[2];

		$mime_message = \Zend\Mime\Message::createFromMessage($message->getBody(), $boundary);

		$message->setBody($mime_message);
	}

	return $message;
}

function personalize_message(\Zend\Mail\Message $message, array $variables)
{
	if ($message->getBody() instanceof \Zend\Mime\Message)
	{
		$body = clone $message->getBody();

		$mime_message_parts = $body->getParts();

		foreach ($mime_message_parts as $position => $part)
		{
			// only replace in text content
			if (!preg_match('/^text\/(.+)(?:;\s+charset=(["\']?)(.+?)\2)$/', $part->type, $match))
				continue;

			$type = $match[1];

			$charset = $match[3];

			// Replace all the stuff
			$content = str_replace_in_mime_content($part->getRawContent(), $type, $charset, $variables);

			// Create a new message part that will contain the new personalized content
			$replacement_part = new \Zend\Mime\Part($content);

			// Copy all the properties from the old message part to the replacement part
			$part_properties = [
				'boundary',
				'charset',
				'description',
				'disposition',
				'encoding',
				'filename',
				'id',
				'language',
				'location',
				'type'];

			foreach ($part_properties as $property)
				$replacement_part->{$property} = $part->{$property};

			// And finally, replace it in the MIME message
			$mime_message_parts[$position] = $replacement_part;
		}

		$body->setParts($mime_message_parts);
	}
	else // we don't have a multipart message
	{
		$type = preg_match('/^text\/(.+?)/', $message->getHeaders()->get('contenttype')->getFieldValue(), $match)
			? $match[1]
			: 'non-text';
		
		$charset = $message->getEncoding();

		$body = $type != 'non-text'
			? str_replace_in_mime_content($message->getBody(), $type, $charset, $variables)
			: $message->getBody();
	}

	$personalized_message = clone $message;
	$personalized_message->setBody($body);
	return $personalized_message;
}

function str_replace_in_mime_content($raw_content, $type, $charset, array $variables)
{
	return str_replace(
		array_keys($variables),
		array_map(
			function($value) use ($type, $charset) {
				switch ($type) {
					// If it is HTML, escape it properly
					case 'html':
						if ($value instanceof Link)
							return $value->toHTML($charset);
						else
							return htmlentities(
								convert_utf8_to_encoding($value, $charset), 
								ENT_COMPAT, $charset);
						break;
					// Otherwise assume no escaping is required
					default:
						if ($value instanceof Link)
							return $value->toText($charset);
						else
							return convert_utf8_to_encoding($value, $charset);
						break;
				}
			},
			array_values($variables)
		),
		$raw_content
	);
}

function process_message_committee(\Zend\Mail\Message $message, $to, &$committee)
{
	$commissie_model = get_model('DataModelCommissie');

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

		$personalized_message = personalize_message($message, $variables);

		echo send_message($personalized_message->toString(), $member->get('email')), "\n";
	}

	return 0;
}

function process_message_mailinglist(\Zend\Mail\Message $message, $to, $from, &$lijst)
{
	$mailinglijsten_model = get_model('DataModelMailinglijst');

	// Find that mailing list
	if (!($lijst = $mailinglijsten_model->get_lijst($to)))
		return RETURN_COULD_NOT_DETERMINE_LIST;

	// Append '[Cover]' or whatever tag is defined for this list to the subject
	// but do so only if it is set.
	if (!empty($lijst->get('tag')))
		$message->setSubject(preg_replace(
			'/^Subject: (?!(?:Re:\s*)?\[' . preg_quote($lijst->get('tag'), '/') . '\])(.+?)$/im',
			'Subject: [' . $lijst->get('tag') . '] $1',
			$message->getSubject()));

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

		// Personalize the message for the receiver
		$variables = array(
			'[NAAM]' => $aanmelding->get('naam'),
			'[NAME]' => $aanmelding->get('naam'),
			'[MAILINGLIST]' => $lijst->get('naam')
		);

		if ($aanmelding->has('lid_id'))
			$variables['[LID_ID]'] = $aanmelding->get('lid_id');

		// If you are allowed to unsubscribe, parse the placeholder correctly (different for opt-in and opt-out lists)
		if ($lijst->get('publiek'))
		{
			$url = $lijst->get('type')== DataModelMailinglijst::TYPE_OPT_IN
				? ROOT_DIR_URI . sprintf('mailinglijsten.php?abonnement_id=%s', $aanmelding->get('abonnement_id'))
				: ROOT_DIR_URI . sprintf('mailinglijsten.php?lijst_id=%d', $lijst->get('id'));

			$variables['[UNSUBSCRIBE_URL]'] = $url;

			$variables['[UNSUBSCRIBE_LINK]'] = new Link($url, sprintf('Click here to unsubscribe from the %s mailinglist.', $lijst->get('naam')));
		}

		$personalized_message = personalize_message($message, $variables);

		echo send_message($personalized_message->toString(), $aanmelding->get('email')), "\n";
	}

	return 0;
}

function process_return_to_sender(\Zend\Mail\Message $message, $from, $destination, $return_code)
{
	$notice = 'Sorry, but your message' . ($destination ? ' to ' . $destination : '') . " could not be delivered:\n" . get_error_message($return_code);

	echo "Return message to sender $from\n";

	// Todo: implement this using \Zend\Mail\Message
	$message_part = \Cover\email\MessagePart::parse_text($message->toString());

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
	$message->setHeader('From', sprintf('%s <%s>', $lijst->get('naam'), $lijst->get('adres')));
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

	// $sendmail = proc_open('cat -- ', $descriptors, $pipes, $cwd, $env);

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
		fwrite(STDERR, get_error_message($return_value) . "\n");

	return $return_value;
}

function main()
{
	$raw_message = stream_get_contents(STDIN);

	if ($raw_message === false || trim($raw_message) == '')
		return RETURN_FAILURE_MESSAGE_EMPTY;

	// Skip the first line because that envelope-to thingy is not part of the actual email
	$message = parse_message($raw_message);
	
	$lijst = null;
	$comissie = null;

	if (!$message->getHeaders()->get('From')->getAddressList()->count())
		return RETURN_COULD_NOT_DETERMINE_SENDER;

	$from = $message->getHeaders()->get('From')->getAddressList()->current();

	if (!$message->getHeaders()->has('Envelope-To'))
		return RETURN_COULD_NOT_DETERMINE_DESTINATION;

	$envelope_to = EnvelopeTo::fromString($message->getHeaders()->get('Envelope-To')->toString());
	
	if ($envelope_to->getAddressList()->count() === 0)
		return RETURN_COULD_NOT_DETERMINE_DESTINATION;

	foreach ($envelope_to->getAddressList() as $destination)
	{
		// First try sending the message to a committee
		$return_code = process_message_committee($message, $destination->getEmail(), $commissie);

		// If that didn't work, try sending it to a mailing list
		if ($return_code == RETURN_COULD_NOT_DETERMINE_COMMITTEE)
		{
			// Process the message: parse it and send it to the list.
			$return_code = process_message_mailinglist($message, $destination->getEmail(), $from->getEmail(), $lijst);
		}

		// Archive the message.
		$archief = get_model('DataModelMailinglijstArchief');
		$archief->archive($raw_message, $from->getEmail(), $lijst, $commissie, $return_code);

		if ($return_code != 0)
			process_return_to_sender($message, $from->getEmail(), $destination->getEmail(), $return_code);
	}

	// Return the result of the processing step.
	return $return_code;
}

exit(verbose(main()));
