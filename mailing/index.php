<?php
function link_site($rel = '')
{
	return sprintf('http://www.svcover.nl/%s', $rel);
}

function default_newsletter()
{
	$newsletter = new Newsletter('newsletter.phtml');

	$agenda = new Newsletter_Section_Agenda('Agenda');
	$agenda->footer = "Every week there is a DomBo (Thursday Afternoon Social) in the SLACK at 16:00."
					. "\n\n"
					. "The [complete agenda](" . link_site('agenda.php') . ") is available in multiple formats.";
	
	$ingehamerd = new Newsletter_Section_CommitteeChanges('New committee members');
	$ingehamerd->footer = 'Have lots of fun in your new committees!';

	$uitgehamerd = new Newsletter_Section_CommitteeChanges('Discharged');
	$uitgehamerd->footer = 'Thanks for all your efforts in these committees!';

	$colofon = new Newsletter_Section_Markdown('Colophon');
	$colofon->data = "This is the newsletter that is send bi-weekly to all of our members.\n\n"
			 . "Content for this newsletter can be send to [kopij@svcover.nl](mailto:kopij@svcover.nl).\n\n"
			 . "In order to subscribe to the more frequent mailing list of Cover ([mailing@svcover.nl](mailto:mailing@svcover.nl)), send an e-mail to [administratie@svcover.nl](mailto:administratie@svcover.nl) with the subject \"Mailing\".";

	$newsletter->sidebar = array($agenda, $ingehamerd, $uitgehamerd, $colofon);

	$newsletter->main = array(
		new Newsletter_Section_Markdown('Announcements of committees'),
		new Newsletter_Section_Markdown('Announcements of the board'));

	return $newsletter;
}

require_once 'include/document.php';
require_once 'include/newsletter.php';
require_once 'include/newsletter_section.php';
require_once 'include/newsletter_section_agenda.php';
require_once 'include/newsletter_section_committeechanges.php';
require_once 'include/newsletter_section_markdown.php';
require_once 'include/newsletterarchive.php';

require_once '../include/init.php';
require_once 'markup.php';
require_once 'markdown.php';

$archive = new NewsletterArchive(dirname(__FILE__) . '/archive');

$javascript = <<< EOF
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="edit.js"></script>
EOF;

$stylesheet = <<< EOF
<link rel="stylesheet" href="edit.css">
EOF;

if (isset($_GET['session']))
	$temp_id = $_GET['session'];
else
{
	header('Location: index.php?session=' . uniqid());
	exit;
}

if (isset($_SESSION['newsletter_' . $temp_id]))
	$newsletter = $_SESSION['newsletter_' . $temp_id];
else
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
	}
}
else if (isset($_GET['section']))
	echo $newsletter->render_section($_GET['section'], $_GET['mode']);
else if (isset($_GET['mode']) && $_GET['mode'] == 'text')
{
	header('Content-Type: text/plain');
	echo $newsletter->render_plain();
}
else if (isset($_GET['mode']) && $_GET['mode'] == 'edit')
{
	$html = $newsletter->render();

	// Add additional CSS
	$html = str_replace('</head>', $stylesheet . '</head>', $html);

	// Add JavaScript
	$html = str_replace('</body>', $javascript . '</body>', $html);

	header('Content-Type: text/html');
	echo $html;
}
else
{
	header('Content-Type: text/html');
	echo $newsletter->render();
}

$_SESSION['newsletter_' . $temp_id] = $newsletter;