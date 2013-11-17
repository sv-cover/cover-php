<?php 

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
