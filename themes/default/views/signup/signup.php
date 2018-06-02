<?php

require_once 'include/csv.php';

class SignUpView extends View
{
	protected $__file = __FILE__;

	public function available_committees()
	{
		$committees = array();

		$model = get_model('DataModelCommissie');

		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) || get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			foreach ($model->get(null, true) as $commissie)
				$committees[$commissie->get_id()] = $commissie->get('naam');
		else
			foreach (get_identity()->member()->get('committees') as $commissie)
				$committees[$commissie] = $model->get_naam($commissie);

		return $committees;
	}

	public function available_activitees()
	{
		$committees = $this->available_committees();

		$activities = get_model('DataModelAgenda')->find([
			'committee_id__in' => array_keys($committees),
			'van__gt' => new DateTime()
		]);

		$options = [];

		foreach ($activities as $activity)
			$options[$activity['id']] = sprintf('(%s) %s', $activity['committee__naam'], $activity['kop']);

		return $options;
	}

	public function available_field_types()
	{
		return get_model('DataModelSignUpField')->field_types;
	}

	public function render_csv(array $entries)
	{
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Content-Disposition: attachment; filename="almanak.csv"');

		if (count($entries) === 0)
			return;
		
		$delim = ','; // Used to be ';'
		$lb = "\r\n"; // Line break

		// Add Unicode byte order marker for Excel
		echo chr(239) . chr(187) . chr(191);

		// print the column headers
		echo csv_row(array_keys($entries[0]), $delim), $lb;

		foreach ($entries as $entry)
			echo csv_row($entry, $delim), $lb;
	}
}