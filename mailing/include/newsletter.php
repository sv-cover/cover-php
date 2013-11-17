<?php

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