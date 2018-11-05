<?php

class LayoutViewHelper
{
	public function top_menu()
	{
		$menus = [];
		
		$menus['home'] = [
			'label' => __('Home'),
			'url' => 'index.php'
		];

		$menus['vereniging'] = [
			'label' => __('Association'),
			'submenu' => [
				[
					'url' => 'commissies.php?commissie=board',
					'label' => __('Governing Board')
				],
				[
					'url' => 'besturen.php',
					'label' => __('Former Boards')
				],
				[
					'url' => 'commissies.php',
					'label' => __('Committees')
				],
				[
					'url' => 'workinggroups.php',
					'label' => __('Working groups')
				],
				[
					'url' => 'show.php?id=28',
					'label' => __('Sister unions')
				],
				[
					'url' => 'show.php?id=18',
					'label' => __('Become a member/donor')
				],
				[
					'url' => 'show.php?id=30',
					'label' => __('Documents'),
				],
				[
					'url' => 'weblog.php',
					'label' => __('Weblog')
				]
			]
		];

		$menus['leden'] = [
			'label' => __('Members'),
			'submenu' => [
				['url' => 'almanak.php', 'label' => __('Almanac')],
				['url' => 'https://wiki.svcover.nl/', 'target' => '_blank', 'label' => __('Wiki')],
				['url' => 'https://sd.svcover.nl/', 'target' => '_blank', 'label' => __('Documents & Templates')],
				['url' => 'stickers.php', 'label' => __('Sticker map')],
				['url' => 'https://merchandise.svcover.nl/', 'target' => '_blank', 'label' => __('Merchandise')],
				['url' => 'dreamspark.php', 'label' => __('Microsoft Imagine')]
			]
		];

		$menus['bedrijven'] = [
			'label' => __('Companies'),
			'submenu' => [
				['url' => 'show.php?id=51', 'label' => __('Company profiles')],
				['url' => 'show.php?id=54', 'label' => __('Vacancies')],
				['url' => 'show.php?id=31', 'label' => __('Internships/Graduate programs')],
				['url' => 'show.php?id=56', 'label' => __('Sponsorship opportunities')]
			]
		];

		$menus['forum'] = [
			'url' => 'forum.php',
			'label' => __('Forum')
		];

		$menus['fotoboek'] = [
			'url' => 'fotoboek.php',
			'label' => __('Photos'),
			'title' => __('Photos of activities of Cover.')
		];

		$menus['studie'] = [
			'label' => __('Study'),
			'submenu' => [
				['url' => 'show.php?id=149', 'label' => __('Degree Programmes')],
				['url' => 'show.php?id=24', 'label' => __('Alumni')],
				['url' => 'boeken.php', 'label' => __('Order books')],
				['url' => 'show.php?id=27', 'label' => __('Student info')],
				['url' => 'show.php?id=118', 'label' => __('Student representation')],
				['url' => 'https://studysupport.svcover.nl/', 'target' => '_blank', 'label' => __('Exams & Summaries')],
				['url' => 'https://tutoring.svcover.nl/', 'target' => '_blank', 'label' => __('Tutoring')]
			]
		];

		$menus['contact'] = [
			'label' => __('Contact'),
			'url' => 'show.php?id=17'
		];

		$menus['admin'] = [
			'label' => __('Tools'),
			'title' => __('These are tools available to you because you are a member of a committee.'),
			'className' => 'authorized-only',
			'submenu' => []
		];

		if (get_identity()->member_in_committee()) { // Member in any committee at all
			$menus['admin']['submenu'][] = [
				'url' => 'https://webmail.svcover.nl/',
				'label' => __('Webmail'),
				'target' => '_blank',
				'title' => __('Webmail for Cover email accounts.')
			];

			$menus['admin']['submenu'][] = [
				'url' => 'mailinglijsten.php',
				'label' => __('Mailing lists'),
				'title' => __('Manage your committee\'s mailing lists.')
			];

			$menus['admin']['submenu'][] = [
				'url' => 'signup.php',
				'label' => __('Forms'),
				'title' => __('Manage your committee\'s sign-up forms.')
			];
		}

		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) ||
			get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR) ||
			get_identity()->member_in_committee(COMMISSIE_EASY)) {
			$menus['admin']['submenu'][] = [
				'url' => 'show.php?view=create',
				'label' => __('Make a page'),
				'title' => __('Make a new content page on the website.')
			];

			$menus['admin']['submenu'][] = [
				'url' => 'lidworden.php?view=pending-confirmation',
				'label' => __('Pending registrations'),
				'title' => __('People who signed up for Cover, but did not yet confirm their email address.')
			];

			// $menus['admin']['submenu'][] = [
			// 	'url' => 'forum.php?admin=forums',
			// 	'label' => 'Forum'
			// ];
		}

		if (get_identity() -> member_in_committee(COMMISSIE_BESTUUR) ||
			get_identity() -> member_in_committee(COMMISSIE_KANDIBESTUUR)) {
			$menus['admin']['submenu'][] = [
				'url' => 'agenda.php?agenda_moderate',
				'label' => __('Calendar'),
				'title' => __('Moderate calendar items.')
			];

			$menus['admin']['submenu'][] = [
				'url' => 'actieveleden.php',
				'label' => __('Active Members'),
				'title' => __('All active committee members according to the website.')
			];
		}
		
		if (get_identity()->member_in_committee(COMMISSIE_EASY)) {
			$menus['admin']['submenu'][] = [
				'url' => 'settings.php',
				'label' => __('Settings'),
				'title' => __('Manage a few of the website\'s settings.')
			];
		}
		
		// Filter out any empty menu items (I'm looking at you, admin menu!)
		$menus = array_filter($menus, function($menu) {
			return isset($menu['url']) || !empty($menu['submenu']);
		});

		return $menus;
	}

	public function agenda()
	{
		$model = get_model('DataModelAgenda');

		return array_filter($model->get_agendapunten(), [get_policy($model), 'user_can_read']);
	}

	public function jarigen()
	{
		$model = get_model('DataModelMember');
		
		$jarigen = $model->get_jarigen();

		return array_filter($jarigen, function($member) use ($model) {
			return !$member->is_private('naam') && !$member->is_private('geboortedatum');
		});
	}

	public function is_cover_jarig()
	{
		return date('m-d') == '09-20';
	}

	public function cover_leeftijd()
	{
		return date('Y') - 1993;
	}

	public function agenda_items_to_moderate()
	{
		/* Check for moderates */
		$model = get_model('DataModelAgenda');
		return array_filter($model->get_proposed(), [get_policy($model), 'user_can_moderate']);
	}

	public function has_alert()
	{
		return isset($_SESSION['alert']) && $_SESSION['alert'] != '';
	}

	public function pop_alert()
	{
		$alert = $_SESSION['alert'];

		unset($_SESSION['alert']);

		return $alert;
	}

	public function promotional_header()
	{
		if (!get_auth()->logged_in())
			return 'promotional-header.twig';
		
		if (basename($_SERVER['SCRIPT_NAME']) == 'index.php'
			&& get_config_value('committee_battle', false))
			return 'committee-battle-header.twig';

		return null;
	}

	public function committee_battle_photos()
	{
		// $committees = get_identity()->get('committees');

		// $model = get_model('DataModelCommitteeBattleScore');

		// $scores = $model->get_scores_for_committees(array_map(function($id) {
		// 	return ['id' => $id];
		// }, $committees));

		$committee_model = get_model('DataModelCommissie');
		
		$committees = $committee_model->get(DataModelCommissie::TYPE_COMMITTEE);

		$committee_photos = array_map(getter('thumbnail'), $committees);
		$committee_photos = array_values(array_filter($committee_photos));
		// shuffle($committee_photos);

		return $committee_photos;
	}
}