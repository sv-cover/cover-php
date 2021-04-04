#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace Cover\email\mailinglist\queue;

chdir(dirname(__FILE__) . '/..');

require_once 'src/init.php';
require_once 'src/framework/email.php';
require_once 'src/framework/router.php';
require_once 'src/framework/send-mailinglist-mail.php';

use \Cover\email\MessagePart;
use function \Cover\email\mailinglist\send_mailinglist_mail;
use function \Cover\email\mailinglist\validate_message_to_all_committees;
use function \Cover\email\mailinglist\validate_message_to_committee;
use function \Cover\email\mailinglist\validate_message_to_mailinglist;
use function \Cover\email\mailinglist\parse_email_address;
use function \Cover\email\mailinglist\send_welcome_mail;
use function \Cover\email\mailinglist\send_message;
use function \Cover\email\mailinglist\get_error_message as _get_error_message;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('RETURN_UNKNOWN_LIST_TYPE', 601);

function send_message_to_all_committees(MessagePart $message, string $to, string $from): int
{
    $committee_model = get_model('DataModelCommissie');

    $destinations = null;
    $loop_id = null;

    $result = validate_message_to_all_committees($message, $to, $from, $destinations, $loop_id);

    if ($result !== 0)
        return $result;

    $message->addHeader('X-Loop', $loop_id);

    $committees = $committee_model->get($destinations[$to]); // Get all committees of that type, not including hidden committees (such as board)

    foreach ($committees as $committee)
    {
        $email = $committee['login'] . '@svcover.nl';

        echo date("Y-m-d H:i:s") . " - Sending mail for " . $to . " to " . $committee['naam'] . " <" . $email . ">: ";
        
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

function send_message_to_committee(MessagePart $message, string $to, \DataIterCommissie &$committee=null): int
{
    $commissie_model = get_model('DataModelCommissie');

    $committee = null;
    $loop_id = null;

    $result = validate_message_to_committee($message, $to, $committee, $loop_id);

    if ($result !== 0)
        return $result;

    $message->addHeader('X-Loop', $loop_id);

    $members = $committee->get_members();

    foreach ($members as $member)
    {
        echo date("Y-m-d H:i:s") . " - Sending mail for " . $to . " to " . $member['voornaam'] . " <" . $member['email'] . ">: ";

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

function send_message_to_mailinglist(MessagePart $message, string $to, string $from, \DataIterMailinglist &$list=null): int
{
    $mailinglist_model = get_model('DataModelMailinglist');
    $router = get_router();

    $list = null;
    $subscriptions = null;
    $loop_id = null;

    $result = validate_message_to_mailinglist($message, $to, $from, $list, $subscriptions, $loop_id);

    if ($result !== 0)
        return $result;

    $message->addHeader('X-Loop', $loop_id);

    // Append '[Cover]' or whatever tag is defined for this list to the subject
    // but do so only if it is set.
    if (!empty($list['tag']))
        $message->setHeader('Subject', preg_replace(
            '/^(?!(?:Re:\s*)?\[' . preg_quote($list['tag'], '/') . '\])(.+?)$/im',
            '[' . $list['tag'] . '] $1',
            $message->header('Subject'), 1));


    if ($list->sends_email_on_first_email() && !$list['archive']->contains_email_from($from))
        send_welcome_mail($list, $from);

    foreach ($subscriptions as $subscription)
    {
        // Skip subscriptions without an e-mail address silently
        if (trim($subscription['email']) == '')
            continue;

        echo date("Y-m-d H:i:s") . " - Sending mail for " . $to . " to " . $subscription['naam'] . " <" . $subscription['email'] . ">: ";

        $unsubscribe_url = $router->generate('mailing_lists', ['abonnement_id' => $subscription['abonnement_id']], UrlGeneratorInterface::ABSOLUTE_URL);
        $archive_url =  $router->generate('mailing_lists', ['view' => 'archive_index', 'id'=> $list['id']], UrlGeneratorInterface::ABSOLUTE_URL);

        // Personize the message for the receiver
        $personalized_message = \Cover\email\personalize($message, function($text, $content_type) use ($subscription, $list, $unsubscribe_url, $archive_url) {
            $use_html = $content_type !== null && preg_match('/^text\/html/', $content_type);

            // Escape function depends on content type (text/html is treated differently)
            $escape = $use_html
                ? function($text, $entities = ENT_COMPAT) { return htmlspecialchars($text, $entities, 'utf-8'); }
                : function($text, $entities = null) { return $text; };

            $variables = array(
                '[NAAM]' => $escape($subscription['naam']),
                '[NAME]' => $escape($subscription['naam']),
                '[MAILINGLIST]' => $escape($list['naam'])
            );

            if ($subscription['lid_id'])
                $variables['[LID_ID]'] = $subscription['lid_id'];

            $variables['[UNSUBSCRIBE_URL]'] = $escape($unsubscribe_url, ENT_QUOTES);

            $variables['[UNSUBSCRIBE]'] = sprintf(($use_html
                ? '<a href="%s">Click here to unsubscribe from the %s mailinglist.</a>'
                : 'To unsubscribe from the %2$s mailinglist, go to %1$s'),
                    $escape($unsubscribe_url),
                    $escape($list['naam']));

            // Add an unsubscribe link to the footer when there isn't already a link in there, and
            // if users can unsubscribe from the list (i.e. public lists)
            if ($content_type !== null
                && $list['publiek']
                && strpos($text, '[UNSUBSCRIBE]') === false
                && strpos($text, '[UNSUBSCRIBE_URL]') === false)
                $text .= sprintf($use_html
                    ? "<div><hr style=\"border:0;border-top:1px solid #ccc\"><small>You are receiving this mail because you are subscribed to the %s mailinglist. [UNSUBSCRIBE]</small></div>"
                    : "\n\n---\nYou are receiving this mail because you are subscribed to the %s mailinglist. [UNSUBSCRIBE]",
                    $escape($list['naam']));

            return str_ireplace(array_keys($variables), array_values($variables), $text);
        });

        $personalized_message->setHeader('List-Unsubscribe', sprintf('<%s>', $unsubscribe_url));
        $personalized_message->setHeader('List-Archive', sprintf('<%s>', $archive_url));

        echo send_message($personalized_message, $subscription['email']), "\n";
    }

    return 0;
}

function get_error_message(int $return_value): string
{
    switch ($return_value)
    {
        case RETURN_UNKNOWN_LIST_TYPE:
            return "Error: Unknown list type.";

        default:
            return _get_error_message($return_value );
    }
}

function process_mailinglist_queue(): int
{
    $queue_model = get_model('DataModelMailinglistQueue');

    $queue = $queue_model->find(['status' => 'waiting']);

    foreach ($queue as $queued_message)
    {
        $queued_message->set('status', 'processing');
        $queued_message->set('processing_on', new \DateTime());
        $queued_message->update();

        $message = MessagePart::parse_text($queued_message->get('message'));
        $from = parse_email_address($message->header('From'));

        if ($queued_message->get('destination_type') === 'all_committees')
            $result = send_message_to_all_committees($message, $queued_message->get('destination'), $from);
        elseif ($queued_message->get('destination_type') === 'committee')
            $result = send_message_to_committee($message, $queued_message->get('destination'));
        elseif ($queued_message->get('destination_type') === 'mailinglist')
        {
            $mailinglist = $queued_message->get('mailinglist');
            $result = send_message_to_mailinglist($message, $queued_message->get('destination'), $from, $mailinglist);
        }
        else
            $result = UNKNOWN_LIST_TYPE;

        if ($result === 0)
           $queue_model->delete($queued_message);
        else 
        {
            $message = get_error_message($result);
            $queued_message->set('status', sprintf('error_%s', $message));
            $queued_message->update();
        }
    }

    return 0;
}

function main(): int
{
    return process_mailinglist_queue();
}

exit(main());
