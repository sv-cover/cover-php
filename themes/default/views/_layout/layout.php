<?php
require_once 'include/models/DataModelPartner.php';

class LayoutViewHelper
{
	private $_partners = null;

	public function top_menu()
	{
		$menus = [];

		$menus['activities'] = [
			'label' => __('Activities'),
			'submenu' => [
				[
					'url' => 'agenda.php',
					'label' => __('Calendar'),
					'title' => __('Upcoming activities')
				],
				[
					'url' => 'fotoboek.php',
					'label' => __('Photos'),
					'title' => __('Photos of activities of Cover.')
				]
			]
		];

		$menus['studie'] = [
			'label' => __('Education'),
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

		$menus['career'] = [
			'label' => __('Career'),
			'url' => '/career.php'
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
					'url' => 'clubs.php',
					'label' => __('Clubs')
				],
				[
					'url' => 'show.php?id=28',
					'label' => __('Sister unions')
				],
				[
					'url' => 'show.php?id=18',
					'label' => __('Become a member/contributor')
				],
			]
		];

		$menus['contact'] = [
			'label' => __('Contact'),
			'url' => 'show.php?id=17'
		];
		
		// Filter out any empty menu items (I'm looking at you, admin menu!)
		$menus = array_filter($menus, function($menu) {
			return isset($menu['url']) || !empty($menu['submenu']);
		});

		return $menus;
	}

	public function tools()
	{
		$tools = [];

		$tools['internal'] = [
			'label' => __(''),
			'items' => [
				[
					'icon' => [
						'fa' => 'fas fa-users',
						'color' => 'cover',
					],
					'url' => 'almanak.php',
					'label' => __('Members')
				],
				[
					'icon' => [
						'fa' => 'fas fa-comments',
						'color' => 'cover',
					],
					'url' => 'forum.php',
					'label' => __('Forum')
				],
				[
					'icon' => [
						'fa' => 'fas fa-map-marked-alt',
						'color' => 'cover',
					],
					'url' => 'stickers.php',
					'label' => __('Sticker map')
				],
			]
		];

		$tools['external'] = [
			'label' => __('Tools'),
			'items' => [
				[
					'icon' => [
						'fa' => 'fas fa-book',
						'color' => 'cover',
					],
					'url' => 'https://wiki.svcover.nl/',
					'target' => '_blank',
					'label' => __('Wiki')
				],
				[
					'icon' => [
						'img' => '/images/applications/sd.png',
					],
					'url' => 'https://sd.svcover.nl/',
					'target' => '_blank',
					'label' => __('Documents & Templates')
				],
				[
					'icon' => [
						'fa' => 'fas fa-tshirt',
						'color' => 'cover',
					],
					'url' => 'https://merchandise.svcover.nl/',
					'target' => '_blank', 
					'label' => __('Merchandise')
				],
				[
					'icon' => [
						'fa' => 'fas fa-graduation-cap',
						'color' => 'cover',
					],
					'url' => 'https://studysupport.svcover.nl/',
					'target' => '_blank',
					'label' => __('Exams & Summaries')
				],
				[
					'icon' => [
						'img' => '/images/applications/tutoring.svg',
					],
					'url' => 'https://tutoring.svcover.nl/',
					'target' => '_blank',
					'label' => __('Tutoring')
				]
			]
		];

		$tools['admin'] = [
			'label' => __('Committee'),
			'items' => []
		];

		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) ||
			get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR) ||
			get_identity()->member_in_committee(COMMISSIE_EASY))
			$tools['admin']['label'] = __('Admin');

		if (get_identity()->member_in_committee()) { // Member in any committee at all
			$tools['external']['items'][] = [
				'icon' => [
					'img' => '/images/applications/mail.svg',
				],
				'url' => 'https://webmail.svcover.nl/',
				'label' => __('Webmail'),
				'target' => '_blank',
				'title' => __('Webmail for Cover email accounts.')
			];

			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-mail-bulk',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => 'mailinglijsten.php',
				'label' => __('Mailing lists'),
				'title' => __('Manage your committee\'s mailing lists.')
			];

			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-list-alt',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => 'signup.php',
				'label' => __('Forms'),
				'title' => __('Manage your committee\'s sign-up forms.')
			];
		}

		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) ||
			get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR) ||
			get_identity()->member_in_committee(COMMISSIE_EASY)) {
			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-plus',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => 'show.php?view=create',
				'label' => __('Make a page'),
				'title' => __('Make a new content page on the website.')
			];

			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-user-plus',
					'color' => 'cover',
				],
				'icon' => [
					'fa' => 'fas fa-calendar',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => 'lidworden.php?view=pending-confirmation',
				'label' => __('Pending registrations'),
				'title' => __('People who signed up for Cover, but did not yet confirm their email address.')
			];
		}

		if (get_identity() -> member_in_committee(COMMISSIE_BESTUUR) ||
			get_identity() -> member_in_committee(COMMISSIE_KANDIBESTUUR)) {
			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-user-friends',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => 'actieveleden.php',
				'label' => __('Active members'),
				'title' => __('All active committee members according to the website.')
			];
			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-building',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => 'partners.php',
				'label' => __('Partners'),
				'title' => __('All partner profiles and banners.')
			];
		}

		
		if (get_identity()->member_in_committee(COMMISSIE_EASY)) {
			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-cog',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => 'settings.php',
				'label' => __('Settings'),
				'title' => __('Manage a few of the website\'s settings.')
			];
		}

		// Filter out any empty menu items (I'm looking at you, admin menu!)
		$tools = array_filter($tools, function($tool) {
			return !empty($tool['items']);
		});

		return $tools;
	}

	public function agenda()
	{
		$model = get_model('DataModelAgenda');

		return array_filter($model->get_agendapunten(), [get_policy($model), 'user_can_read']);
	}

	protected function _get_partners(Array $include = [], Array $exclude = [])
	{
		if (!isset($this->_partners)) {
			$model = get_model('DataModelPartner');
			$this->_partners = array_filter($model->find(['has_banner_visible' => 1]), [get_policy($model), 'user_can_read']);
			$model->shuffle($this->_partners);
		}

		if (!empty($include) || !empty($exclude))
			return array_filter($this->_partners, function($partner) use ($include, $exclude) {
				return (empty($include) || in_array($partner['type'], $include))
					&& (empty($exclude) || !in_array($partner['type'], $exclude));
			});

		return $this->_partners;
	}

	public function partners()
	{
		return $this->_get_partners([], [DataModelPartner::TYPE_MAIN_SPONSOR]);
	}

	public function main_partners()
	{
		return $this->_get_partners([DataModelPartner::TYPE_MAIN_SPONSOR]);
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


	public function color_mode()
	{
		return $_COOKIE['cover_color_mode'] ?? 'light';
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
		return false;

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