<?php
require_once 'src/framework/member.php';

class AlmanakView extends View
{
	public function classname(DataIterMember $member)
	{
		switch ($member['type'])
		{
			case MEMBER_STATUS_LID:
				return 'status-lid';
				
			case MEMBER_STATUS_LID_AF:
				return 'status-lid-af';
			
			case MEMBER_STATUS_ERELID:
				return 'status-erelid';
			
			case MEMBER_STATUS_DONATEUR:
				return 'status-donateur';
		}
	}

	public function status_label(DataIterMember $member)
	{
		switch ($member['type'])
		{
			case MEMBER_STATUS_LID:
				return null;

			case MEMBER_STATUS_LID_AF:
				return __('Previously a member');

			case MEMBER_STATUS_ERELID:
				return __('Honorary Member');

			case MEMBER_STATUS_DONATEUR:
				return __('Contributor');
		}
	}

	public function render_csv($iters)
	{
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Content-Disposition: attachment; filename="almanak.csv"');

		// Add Unicode byte order marker for Excel
		echo chr(239) . chr(187) . chr(191);

		$out = fopen('php://output', 'w');

		// column headers
		fputcsv($out, [
			'id',
			'voornaam',
			'tussenvoegsel',
			'achternaam',
			'naam',
			'adres',
			'postcode',
			'woonplaats',
			'email',
			'geboortedatum',
			'telefoonnummer',
			'studie',
			'beginjaar',
			'status',
		]);

		foreach ($iters as $item)
			fputcsv($out, [
				$item['id'],
				$item['voornaam'],
				$item['tussenvoegsel'],
				$item['achternaam'],
				member_full_name($item, IGNORE_PRIVACY),
				$item['adres'],
				$item['postcode'],
				$item['woonplaats'],
				$item['email'],
				$item['geboortedatum'],
				$item['telefoonnummer'],
				$item['studie'],
				$item['beginjaar'],
				$item['status'],
			]);

		exit();
	}

	public function render_index($iters = null, array $params = array())
	{
		$preferred = $this->_get_preferred_response();

		// Set default params for search fields in template
		$params = array_merge([
			'search' => '',
			'year' => null,
			'status' => null
		], $params);

		if ($preferred == 'application/json')
			return json_encode(array_map(function($lid) {
				return array(
					'id' => $lid->get_id(),
					'starting_year' => $lid->get('beginjaar'),
					'name' => member_full_name($lid));
			}, $iters));
		else
			return $this->twig->render('index.twig', compact('iters','params'));
	}
}
