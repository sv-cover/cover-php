<?php

class Newsletter_Section_Agenda extends Newsletter_Section
{
	private $activities = array();

	public function __construct($title)
	{
		parent::__construct($title);

		$this->fetch_activities();
	}

	public function fetch_activities()
	{
		$response = file_get_contents(link_api('agenda'));

		if (!$response) return;

		$result = json_decode($response);

		if (!is_array($result)) return;

		$activities = array();

		$hidden_activities = array();

		foreach ($this->activities as $activity)
			if (!$activity['visible'])
				$hidden_activities[] = $activity['id'];

		foreach ($result as $activity)
			$activities[] = array(
				'id' => $activity->id,
				'vandatum' => $activity->vandatum,
				'vanmaand' => $activity->vanmaand,
				'kop' => $activity->kop,
				'visible' => !in_array($activity->id, $hidden_activities));

		$this->activities = $activities;
	}

	public function render()
	{
		$lines = array();
		foreach ($this->activities as $activity)
			if ($activity['visible'])
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
		$this->fetch_activities();

		$document = parent::render_controls();

		foreach ($this->activities as $activity)
		{
			$document->body .= sprintf('<label><input type="checkbox" name="activity_%d" %s> %s</label><br>',
				$activity['id'],
				$activity['visible'] ? 'checked' : '',
				htmlspecialchars($activity['kop'], ENT_COMPAT, 'utf-8'));
		}

		return $document;
	}

	public function handle_postback($data)
	{
		parent::handle_postback($data);

		foreach ($this->activities as &$activity)
			$activity['visible'] = !empty($data['activity_' . $activity['id']]);
	}
}