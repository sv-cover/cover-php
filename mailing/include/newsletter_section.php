<?php

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
