<?php
require_once '../include/init.php';
require_once 'markup.php';
require_once 'markdown.php';

function link_site($rel = '')
{
	return sprintf('http://www.svcover.nl/%s', $rel);
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

		$this->sidebar = array(
			'agenda' => new Newsletter_Section_Agenda('Agenda'),
			'ingehamerd' => new Newsletter_Section_CommitteeChanges('New committee members'),
			'uitgehamerd' => new Newsletter_Section_CommitteeChanges('Discharged'),
			'colofon' => new Newsletter_Section_Markdown('Colophon')
		);

		$this->main = array(
			'committees' => new Newsletter_Section_Markdown('Announcements of committees'),
			'board' => new Newsletter_Section_Markdown('Announcements of the board')
		);
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
			$lines .= wordwrap($section->render_plain(), 70, "\r\n", true);
			$lines .= "\r\n\r\n";
		}

		return $lines;
	}
}

class Newsletter_Section
{
	public $uniqid;

	public $title;

	public $footer;

	public function __construct($title)
	{
		$this->uniqid = uniqid(get_class($this));

		$this->title = $title;
	}

	protected function render_header()
	{
		return $this->title
			? sprintf('<h2>%s</h2>', htmlentities($this->title, ENT_COMPAT, 'UTF-8'))
			: '';
	}

	protected function render_body()
	{
		return '';
	}

	protected function render_footer()
	{
		return $this->footer
			? Markdown($this->footer)
			: '';
	}

	public function render()
	{
		return $this->render_header()
			 . $this->render_body()
			 . $this->render_footer();
	}

	protected function render_plain_header()
	{
		return "=== {$this->title} ===\r\n\r\n";
	}

	protected function render_plain_body()
	{
		return '';
	}

	protected function render_plain_footer()
	{
		return $this->footer
			? "\r\n\r\n$this->footer"
			: "";
	}

	public function render_plain()
	{
		return $this->render_plain_header()
			 . $this->render_plain_body()
			 . $this->render_plain_footer();
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

		$this->activities = $agenda->get_agendapunten(true);
	}

	protected function render_body()
	{
		$lines = array();
		foreach ($this->activities as $activity)
			$lines[] = sprintf('<span class="date">%02d-%02d</span>&nbsp;<a href="%s" target="_blank">%s</a>',
				$activity->get('vandatum'),
				$activity->get('vanmaand'),
				link_site('agenda.php?agenda_id=' . $activity->get_id()),
				htmlspecialchars($activity->get('kop'), ENT_COMPAT, 'utf-8'));

		return implode("<br>\n", $lines);
	}

	protected function render_plain_body()
	{
		$lines = array();
		foreach ($this->activities as $activity)
			$lines[] = sprintf("%02d-%02d %4\$s\r\n      %3\$s",
				$activity->get('vandatum'),
				$activity->get('vanmaand'),
				link_site('agenda.php?agenda_id=' . $activity->get_id()),
				$activity->get('kop'));

		return implode("\r\n", $lines);
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

	protected function render_body()
	{
		$committees = $this->parse($this->data);

		if (count($committees) == 0)
			return;

		$html = '';

		foreach ($committees as $committee => $members)
		{
			$html .= sprintf('<strong>%s:</strong>', htmlspecialchars($committee, ENT_COMPAT, 'UTF-8'));

			$html .= '<ul style="margin: 0 0 5px 0; padding: 0;">';
			foreach ($members as $member)
				$html .= sprintf('<li style="margin: 0 0 0 16px">%s</li>', htmlspecialchars($member, ENT_COMPAT, 'UTF-8'));
			$html .= '</ul>';
		}

		return $html;
	}

	protected function render_plain_body()
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

		return implode("\r\n", $lines);
	}
}

class Newsletter_Section_Markdown extends Newsletter_Section
{
	public $data;

	protected function render_body()
	{
		return Markdown($this->data);
	}

	protected function render_plain_body()
	{
		return $this->data;
	}
}

$newsletter = new Newsletter('newsletter.phtml');
$newsletter->submission_date = new DateTime('2013-11-11');

$link_agenda = link_site('agenda.php');
$newsletter->sidebar['agenda']->footer = <<< EOF
Every week there is a DomBo (Thursday Afternoon Social) in the SLACK at 16:00.

The [complete agenda]({$link_agenda}) is available in multiple formats.
EOF;

$newsletter->sidebar['uitgehamerd']->footer = 'Thanks for all your efforts in these committees!';
$newsletter->sidebar['uitgehamerd']->data = <<< EOF
AlmanakCie
- Maikel Grobbe
- Hein de Haan
- Martijn Luinstra 
- Arnoud van der Meulen
- Davey Schilling

EerstejaarsCie
- Harmke Alkemade
- Jan van Houten
- Sophie Hugenholtz
- Martijn Luinstra
- Marijn Pool

SLACKcie
- Martijn Luinstra
- Davey Schilling

FotoCie
- Arryon Tijsma
EOF;

$newsletter->sidebar['ingehamerd']->footer = 'Have lots of fun in your new committees!';
$newsletter->sidebar['ingehamerd']->data = <<< EOF
AlmanakCie
- Annemarie Galetzka
- Daniël Haitink
- Diederick Kaaij
- Jip Maijers
- Marten Schutten

BHVcie
- Eveline Broers
- Annemarie Galetzka
- Bastiaan van Loon
- Davey Schilling

EerstejaarsCie
- Robin Entjes
- Anco Gietema
- Johan Groenewold
- Henry Maathuis
- Nicole Mascarenhas

MeisCie
- Lisette Boeijenk
- Rayan Brouwer

SLACKcie
- Arnoud van der Meulen
EOF;

$newsletter->sidebar['colofon']->data = <<< EOF
This is the newsletter that is send bi-weekly to all of our members.

Content for this newsletter can be send to [kopij@svcover.nl](mailto:kopij@svcover.nl).

In order to subscribe to the more frequent mailing list of Cover ([mailing@svcover.nl](mailto:mailing@svcover.nl)), send an e-mail to [administratie@svcover.nl](mailto:administratie@svcover.nl) with the subject "Mailing".
EOF;

$newsletter->main['committees']->data = <<< EOF
### Actie: Movie Night
Date: Tuesday 12 November  
Time: 18:00  
Location: Room 280, Bernoulliborg  

The minions are back! If you want to see the new adventures of these yellow creatures, then make sure you are going to the movie night, because we are going watch [Despicable me 2](http://www.youtube.com/watch?v=TlbnGSMJQbQ). As usual we'll be eating together and order our food at hasret-groningen.nl. If you want to eat with us, place your order at the pizzalist in the SLACK or mail the pizza and pizzanumber to actie@svcover.nl. The deadline for this is tuesday November 12th 17:00.

### Actie: 'Weerwolven' Game Night
Date: Tuesday 19 November  
Time: 20:00  
Location: Café Atlantis  

This month's game night is a special one. We are going to play the game 'Weerwolven'! This game quite similar to the game 'Mafia'. So if you want to join us, you should come to the game night! If you want to play other games, that's possible. We just want you to have an awesome evening. We'll see you there!

### Actie: Sinterklaasavond
Date: Tuesday 26 November  
Time: 17:00  
Location: Room 280, Bernoulliborg  

Sinterklaas is almost arriving in The Netherlands! This means we'll have a sinterklaasavond soon. This evening we are going to play a nice game with hopefully lots of presents. Do you want to be a 'Hulpsint' and make this a nice evening? You'll have to get at least 2 presents for about €5,-. Sign up by sending an email to actie@svcover.nl. The deadline is Tuesday November 19th 23:59.

### MeisCie: Ballroom and Latin Dancing
Date: Monday 25 November  
Time: 17:00  
Location: To be announced  

The MeisCie has something special in store for you. For our first activity this year we have prepared a dancing lesson. We're not talking about dance moves you'll see at the Mambo Jambo or the Negende Cirkel. We're talking about ballroom and latin dancing! We'll teach you the basic steps of the chachacha and the quick step. For the chachacha we have prepared a few additions to make the dance more interesting. We all hope to see you and hope you'll have a good time dancing!

### StudCie: Lunch Lecture TNO
Date: Wednesday 20 November  
Time: 13:00  
Location: Room 5173.0151, Linaeusborg  

TNO will be giving a lecture on both information security and home automation.

First, Gerben Broenink will show the possibilities for injecting malware into existing android applications. He will be using a manipulated Angry Birds app during the demo to gain control of a smartphone.

Second, Wilco Wijbrandi will show the FlexiblePower Application Infrastructure (FPAI), which is a home automation platform which can connect with all the smart devices in your home. This platform can be used to install applications that control your devices or connect with the smart grid.

If you want a free lunch, you have to register at [our website](http://studcie.svcover.nl).
EOF;

$newsletter->main['board']->data = <<< EOF
### New logo
You may have seen some spoofs on the screen in the Coverkamer, but we have a new official board logo.
<img alt="Logo Board XXII of Cover" src="http://svcover.nl/images/bestuurxxii.png" width="400" height="225">

### New newsletter
Hopefully you have already noticed that this newsletter differs a lot from the previous one. Because we were busy designing our new newsletter, we moved this newsletter from one week ago to this Monday. The next newsletter will be sent two weeks from now.

### New committee
We have a new committee! The BHVcie is now a real committee and in this newsletter we would like to inform you about this matter.

The BHVcie has two main tasks. They have to provide BHV’ers (ERO’s in Dutch) for relevant events. They also have to maintain the first aid kits. So if you need a BHV’er for an event, you can mail the committee at bhv@svcover.nl and they will provide the amount of BHV’ers needed. 

The people in this committee are Annemarie Galetzka, Bastiaan van Loon, Davey Schilling and Eveline Broers. Soon we will also have a fifth member for the committee.

### Idea submission station
In the last couple of weeks also our new website launched: [idee.svcover.nl](http://idea.svcover.nl)! You can use it when you suddenly have inspiration. The ideas will be discussed during our board meeting.
EOF;

if (!isset($_GET['mode']))
{	
	echo '<style>html, body, iframe {margin:0;padding:0;border:0}</style>';
	echo '<iframe style="border:none;" width="50%" height="100%" src="index.php?mode=html"></iframe>';
	echo '<iframe style="border:none;" width="50%" height="100%" src="index.php?mode=text"></iframe>';
}
else if ($_GET['mode'] == 'html')
{
	header('Content-Type: text/html; charset=utf-8');
	echo $newsletter->render();
}
else if ($_GET['mode'] == 'text')
{
	header('Content-Type: text/plain; charset=utf-8');
	echo $newsletter->render_plain();
}
