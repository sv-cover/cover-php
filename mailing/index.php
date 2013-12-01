<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

require_once 'include/document.php';
require_once 'include/newsletter.php';
require_once 'include/newsletter_section.php';
require_once 'include/newsletter_section_agenda.php';
require_once 'include/newsletter_section_committeechanges.php';
require_once 'include/newsletter_section_markdown.php';
require_once 'include/newsletterarchive.php';
require_once 'markdown.php';
require_once 'include/session.php';
require_once 'include/template.php';

session_start();

function link_site($rel = '')
{
	return sprintf('http://www.svcover.nl/%s', $rel);
}

function link_api($method)
{
	return sprintf('http://www.svcover.nl/api.php?method=%s', $method);
}

function default_newsletter()
{
	$newsletter = new Newsletter('newsletter.phtml');

	$agenda = new Newsletter_Section_Agenda('Agenda');
	$agenda->footer = "Every week there is a DoMBo (Thursday Afternoon Social) in the SLACK at 16:00."
					. "\n\n"
					. "The [complete agenda](" . link_site('agenda.php') . ") is available in multiple formats.";
	
	$ingehamerd = new Newsletter_Section_CommitteeChanges('New committee members');
	$ingehamerd->footer = 'Have lots of fun in your new committees!';

	$uitgehamerd = new Newsletter_Section_CommitteeChanges('Discharged');
	$uitgehamerd->footer = 'Thanks for all your efforts in these committees!';

	$colofon = new Newsletter_Section_Markdown('Colophon');
	$colofon->data = "Content for this newsletter can be send to [kopij@svcover.nl](mailto:kopij@svcover.nl).\n\n"
				   . "In order to subscribe to the more frequent mailing list of Cover ([mailing@svcover.nl](mailto:mailing@svcover.nl)), send an e-mail to [administratie@svcover.nl](mailto:administratie@svcover.nl) with the subject \"Mailing\".";

	$newsletter->sidebar = array($agenda, $ingehamerd, $uitgehamerd, $colofon);

	$newsletter->main = array(
		new Newsletter_Section_Markdown('Announcements of committees'),
		new Newsletter_Section_Markdown('Announcements of the board'));

	return $newsletter;
}

function archive_newsletter(Newsletter $newsletter)
{
	$path = sprintf('../newsletter/%s.html',
		$newsletter->submission_date->format('Ymd'));

	if (!file_put_contents($path, $newsletter->render()))
		throw new Exception('Could not archive file');

	$newsletter->log('Archived to ' . $path . ' by ' . NewsletterSession::instance()->currentUser());
}

function submit_newsletter(Newsletter $newsletter, $email)
{
	$notice_text = "This is a multi-part message in MIME format."
				 . "If you cannot read it, go to " . $newsletter->render_permalink();

	$plain_text = $newsletter->render_plain();

	$html_text = $newsletter->render();

	if (!preg_match('{<title>(.+?)</title>}', $html_text, $match))
			throw new Exception('Could not extract subject from newsletter');

	$subject = $match[1];

	$mime_boundary = uniqid('np');

	$message = array(
			$notice_text,
			"",
			"--$mime_boundary",
			"Content-Type: text/plain; charset=UTF-8",
			"Content-Transfer-Encoding: 7bit",
			"",
			$plain_text,
			"",
			"--$mime_boundary",
			"Content-Type: text/html; charset=UTF-8",
			"Content-Transfer-Encoding: 7bit",
			"",
			$html_text,
			"",
			"--$mime_boundary--");

	$headers = array(
			'MIME-Version: 1.0',
			'From: Bestuur Cover <bestuur@svcover.nl>',
			'Reply-To: Bestuur Cover <bestuur@svcover.nl>',
			'Content-Type: multipart/alternative;boundary=' . $mime_boundary);

	if(!mail($email,
			$subject,
			implode("\r\n", $message),
			implode("\r\n", $headers)))
		throw new Exception('mail() failed');

	$newsletter->log('Submitted to ' . $email . ' by ' . NewsletterSession::instance()->currentUser());
}

$archive = new NewsletterArchive(dirname(__FILE__) . '/archive');

$javascript = <<< EOF
<script src="//code.jquery.com/jquery-1.9.1.js"></script>
<script src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script src="edit.js"></script>
EOF;

$stylesheet = <<< EOF
<link rel="stylesheet" href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="edit.css">
EOF;

$session = NewsletterSession::instance();

if (isset($_GET['session']))
	$temp_id = $_GET['session'];
else
	$temp_id = uniqid();

if (!$session->loggedIn())
{
	$error = null;

	if (isset($_POST['email'], $_POST['password']))
	{
		try {
			NewsletterSession::login($_POST['email'], $_POST['password']);
			header('Location: index.php?session=' . $temp_id);
			exit;
		} catch (Exception $e) {
			$error = $e->getMessage();
		}
	}

	echo render_template('tpl/login.phtml', compact('error'));
	exit;
}

if (!isset($_GET['session']))
{
	header('Location: index.php?session=' . $temp_id);
	exit;
}

if (!($newsletter = $session->get($temp_id)))
	$newsletter = default_newsletter();

if (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'reset':
			$newsletter = default_newsletter();
			echo 'Newsletter reset to template';
			break;

		case 'save':
			try {
				$archive->save($newsletter, $_POST['name']);
				echo 'Newsletter saved as ' . $_POST['name'];
			} catch(Exception $e) {
				echo 'Could not save newsletter: ' . $e->getMessage();
			}
			break;

		case 'load':
			try {
				$newsletter = $archive->load($_POST['name']);
				echo 'Newsletter loaded';
			}
			catch (Exception $e) {
				echo 'Could not load newsletter: ' . $e->getMessage();
			}
			break;

		case 'set-date':
			try {
				$newsletter->submission_date = new DateTime($_POST['date']);
				echo 'Newsletter date changed to ' . $newsletter->submission_date->format('l j F Y');
			}  catch (Exception $e) {
				echo 'Could not change date: ' . $e->getMessage();
			}
			break;

		case 'submit':
			try {
				archive_newsletter($newsletter);
				submit_newsletter($newsletter, $_POST['email']);
				echo 'Newsletter archived and submitted to ' . $_POST['email'];
			} catch (Exception $e) {
				echo 'Could not submit newsletter: ' . $e->getMessage();
			}
			break;

		case 'destroy-session':
			try {
				$session->destroy();
				echo 'You have been logged out';
			} catch (Exception $e) {
				echo 'Could not log you out: ' . $e->getMessage();
			}
			break;
	}
}
else if (isset($_GET['section']))
{
	echo $newsletter->render_section($_GET['section'],
		isset($_GET['mode']) ? $_GET['mode'] : 'html');
}
else if (isset($_GET['mode']) && $_GET['mode'] == 'session')
{
	header('Content-Type: application/json');
	echo json_encode($session->currentUser());
}
else if (isset($_GET['mode']) && $_GET['mode'] == 'log')
{
	header('Content-Type: text/plain');

	if (!count($newsletter->log))
		echo 'No messages.';
	else
		foreach ($newsletter->log as $message)
			printf("%s: %s\n", $message[0]->format("Y-m-d H:i"), $message[1]);
}
else if (isset($_GET['mode']) && $_GET['mode'] == 'listing')
{
	header('Content-Type: applicaton/json');
	echo json_encode($archive->listing());
}
else if (isset($_GET['mode']) && $_GET['mode'] == 'text')
{
	header('Content-Type: text/plain');
	echo $newsletter->render_plain();
}
else if (isset($_GET['mode']) && $_GET['mode'] == 'html')
{
	header('Content-Type: text/html; charset=UTF-8');
	echo $newsletter->render();
}
else
{
	$html = $newsletter->render();

	// Add additional CSS
	$html = str_replace('</head>', $stylesheet . '</head>', $html);

	// Add JavaScript
	$html = str_replace('</body>', $javascript . '</body>', $html);

	header('Content-Type: text/html; charset=UTF-8');
	echo $html;
}

$session->set($temp_id, $newsletter);