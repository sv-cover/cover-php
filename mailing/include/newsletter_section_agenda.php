<?php

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