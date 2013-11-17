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

class Newsletter
{

	public $template;

	public $submission_date;

	public $sidebar = array();

	public $main = array();

	public function __construct($template)
	{
		$this->template = $template;

		$this->submission_date = new DateTime();

		$this->sidebar = array();

		$this->main = array();
	}

	public function render_title()
	{
		return sprintf('Cover newsletter %s',
			$this->submission_date->format('jS \o\f F'));
	}

	public function render_permalink()
	{
		return link_site(sprintf('newsletter/%s.html',
			$this->submission_date->format('Ymd')));
	}

	public function style_headers($html)
	{
		return preg_replace(
			'{<h(\d)>(.+?)</h\1>}',
			'<h\1 style="color:#C60C30">\2</h\1>',
			$html);
	}

	public function style_links($html)
	{
		return preg_replace(
			'{<a (.+?)>}',
			'<a style="color:#FFFFFF" $1>',
			$html);
	}

	public function render()
	{
		ob_start();
		include $this->template;
		return ob_get_clean();
	}

	public function render_plain()
	{
		$lines = $this->render_title() . "\r\n\r\n";

		foreach (array_merge($this->main, $this->sidebar) as $section) {
			$lines .= wordwrap(strval($section->render_plain()), 70, "\r\n", true);
			$lines .= "\r\n\r\n";
		}

		return $lines;
	}

	public function render_section($section_id, $mode)
	{
		foreach (array_merge($this->main, $this->sidebar) as $section)
		{
			if ($section->id() != $section_id)
				continue;

			if ($_SERVER['REQUEST_METHOD'] == 'POST')
				$section->handle_postback($_POST);

			if ($mode == 'controls')
				return $section->render_controls();
			else
				return $section->render();
		}
	}
}

class Document
{
	public $header;

	public $body;

	public $footer;

	public $container = '%s%s%s';

	public function __toString()
	{
		return sprintf($this->container, $this->header, $this->body, $this->footer);
	}
}

class Newsletter_Section
{
	private $uniqid;

	public $title;

	public $footer;

	public function __construct($title)
	{
		$this->uniqid = uniqid(get_class($this));

		$this->title = $title;
	}

	public function id()
	{
		return $this->uniqid;
	}

	public function render()
	{
		$document = new Document();

		$document->header = $this->title
			? sprintf('<h2>%s</h2>', htmlentities($this->title, ENT_COMPAT, 'UTF-8'))
			: '';
		
		$document->footer = $this->footer
			? Markdown($this->footer)
			: '';

		return $document;
	}

	public function render_plain()
	{
		$document = new Document();

		$document->header = "=== {$this->title} ===\r\n\r\n";

		$document->footer = $this->footer
			? "\r\n\r\n$this->footer"
			: "";

		return $document;
	}

	public function handle_postback($data)
	{
		$this->title = $_POST['title'];

		$this->footer = $_POST['footer'];
	}

	public function render_controls()
	{
		$document = new Document();

		$document->container = '<form method="post" action="?session=' . $_GET['session'] . '&amp;section=' . $this->id() . '">%s %s %s<button type="submit">Save</button></form>';

		$document->header = '<input type="text" name="title" placeholder="Title" value="' . htmlentities($this->title, ENT_QUOTES, 'utf-8') . '">';
		
		$document->footer = '<textarea name="footer" placeholder="Footer markdown…">' . htmlentities($this->footer, ENT_COMPAT, 'utf-8') . '</textarea>';
		
		return $document;
	}
}

class Newsletter_Section_Agenda extends Newsletter_Section
{
	public function __construct($title)
	{
		parent::__construct($title);

		$this->fetch_activities();
	}

	public function fetch_activities()
	{
		$agenda = get_model('DataModelAgenda');

		$this->activities = array();

		foreach ($agenda->get_agendapunten(true) as $activity)
			$this->activities[] = array(
				'id' => $activity->get_id(),
				'vandatum' => $activity->get('vandatum'),
				'vanmaand' => $activity->get('vanmaand'),
				'kop' => $activity->get('kop'));
	}

	public function render()
	{
		$lines = array();
		foreach ($this->activities as $activity)
			$lines[] = sprintf('<span class="date">%02d-%02d</span>&nbsp;<a href="%s" target="_blank">%s</a>',
				$activity['vandatum'],
				$activity['vanmaand'],
				link_site('agenda.php?agenda_id=' . $activity['id']),
				htmlspecialchars($activity['kop'], ENT_COMPAT, 'utf-8'));

		$document = parent::render();
		$document->body = implode("<br>\n", $lines);
		return $document;
	}

	public function render_plain()
	{
		$lines = array();
		foreach ($this->activities as $activity)
			$lines[] = sprintf("%02d-%02d %4\$s\r\n      %3\$s",
				$activity['vandatum'],
				$activity['vanmaand'],
				link_site('agenda.php?agenda_id=' . $activity['id']),
				$activity['kop']);

		$document = parent::render_plain();
		$document->body = implode("\r\n", $lines);
		return $document;
	}

	public function render_controls()
	{
		$document = parent::render_controls();

		// Add some sort of edit-thingy to delete agenda items

		return $document;
	}

	public function handle_postback($data)
	{
		parent::handle_postback($data);
	}
}

class Newsletter_Section_CommitteeChanges extends Newsletter_Section
{
	public $data = '';

	protected function parse($text)
	{
		$committees = array();

		$committee = null;

		foreach (explode("\n", $text) as $line)
		{
			$line = trim($line);

			if ($line == '')
				continue;
			elseif ($line[0] == '-')
				$committees[$committee][] = ltrim($line, '- ');
			else
				$committee = rtrim($line, ':');
		}

		return $committees;
	}

	public function render()
	{
		$committees = $this->parse($this->data);

		if (count($committees) == 0)
		{
			if (isset($_GET['mode']) && $_GET['mode'] == 'edit')
				return parent::render();
			else 
				return '';
		}

		$html = '';

		foreach ($committees as $committee => $members)
		{
			$html .= sprintf('<strong>%s:</strong>', htmlspecialchars($committee, ENT_COMPAT, 'UTF-8'));

			$html .= '<ul style="margin: 0 0 5px 0; padding: 0;">';
			foreach ($members as $member)
				$html .= sprintf('<li style="margin: 0 0 0 16px">%s</li>', htmlspecialchars($member, ENT_COMPAT, 'UTF-8'));
			$html .= '</ul>';
		}

		$document = parent::render();
		$document->body = $html;
		return $document;
	}

	public function render_plain()
	{
		$committees = $this->parse($this->data);

		if (count($committees) == 0)
			return;

		$lines = array();

		foreach ($committees as $committee => $members)
		{
			$lines[] = sprintf('%s:', $committee);

			foreach ($members as $member)
				$lines[] = sprintf('- %s', $member);
		}

		$document = parent::render_plain();
		$document->body = implode("\r\n", $lines);
		return $document;
	}

	public function render_controls()
	{
		$document = parent::render_controls();

		$document->body = sprintf('<textarea name="data" placeholder="Data">%s</textarea>',
			htmlentities($this->data, ENT_COMPAT, 'utf-8'));

		return $document;
	}

	public function handle_postback($data)
	{
		$this->data = $data['data'];

		return parent::handle_postback($data);
	}
}

class Newsletter_Section_Markdown extends Newsletter_Section
{
	public $data;

	public function render()
	{
		$html = Markdown($this->data);

		$html = $this->recount_headers($html);

		$document = parent::render();
		$document->body = $html;
		return $document;
	}

	public function render_plain()
	{
		$document = parent::render_plain();
		$document->body = $this->data;
		return $document;
	}

	public function render_controls()
	{
		$document = parent::render_controls();

		$document->body = sprintf('<textarea name="data" placeholder="Markdown text">%s</textarea>',
			htmlentities($this->data, ENT_COMPAT, 'utf-8'));

		return $document;
	}

	public function handle_postback($data)
	{
		$this->data = $data['data'];

		return parent::handle_postback($data);
	}

	protected function recount_headers($html)
	{
		return preg_replace_callback(
			'~<h(\d)>(.+?)</h\1>~i',
			function($match) {
				return sprintf('<h%d>%s</h%1$d>', $match[1] + 2, $match[2]);
			},
			$html);
	}
}

class NewsletterArchive
{
	private $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	public function load($filename)
	{
		if (!preg_match('~^[a-z0-9_\-]+$~i', $filename))
			throw new Exception('Invalid file name');

		$path = $this->path . '/' . $filename . '.bin';

		if (!file_exists($path))
			throw new Exception('File not found');

		$data = file_get_contents($path);

		if (!$data)
			throw new Exception('Could not load file');

		$newsletter = unserialize($data);

		if (!($newsletter instanceof Newsletter))
			throw new Exception('Could not parse newsletter');

		// Mark the newsletter as saved.
		$newsletter->unchanged = true;

		return $newsletter;
	}

	public function save(Newsletter $newsletter, $filename)
	{
		if (!preg_match('~^[a-z0-9_\-]+$~i', $filename))
			throw new Exception('Invalid file name');
		
		$path = $this->path . '/' . $filename . '.bin';

		$data = serialize($newsletter);

		if (!$data)
			throw new Exception('Could not encode newsletter');

		if (!file_put_contents($path, $data))
			throw new Exception('Could not write to file');

		// Mark the newsletter as saved.
		$newsletter->unchanged = true;
	}

	public function listing()
	{
		return glob('*.bin');
	}
}

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