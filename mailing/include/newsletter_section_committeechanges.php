<?php

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