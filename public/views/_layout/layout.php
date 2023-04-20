<?php
require_once 'src/models/DataModelPartner.php';

use Symfony\Component\Routing\RouterInterface;

class LayoutViewHelper
{
	private $_partners = null;

	protected $router;

	public function __construct($router)
	{
		$this->router = $router;
	}

	public function top_menu()
	{
		$menus = [];

		$menus['activities'] = [
			'label' => __('Activities'),
			'submenu' => [
				[
					'url' => $this->router->generate('calendar'),
					'label' => __('Calendar'),
					'title' => __('Upcoming activities')
				],
				[
					'url' => $this->router->generate('photos'),
					'label' => __('Photos'),
					'title' => __('Photos of Cover\'s activities.')
				]
			]
		];

		$menus['studie'] = [
			'label' => __('Education'),
			'submenu' => [
				['url' => $this->router->generate('page', ['id' => 149]), 'label' => __('Degree Programmes')],
				// ['url' => $this->router->generate('page', ['id' => 24]),  'label' => __('Alumni')],
				['url' => $this->router->generate('page', ['id' => 27]),  'label' => __('Student info')],
				['url' => $this->router->generate('page', ['id' => 118]), 'label' => __('Student representation')],
				['url' => 'https://studysupport.svcover.nl/', 'target' => '_blank', 'label' => __('Exams & Summaries')],
				['url' => 'https://tutoring.svcover.nl/', 'target' => '_blank', 'label' => __('Tutoring')]
			]
		];

		$menus['career'] = [
			'label' => __('Career'),
			'url' => $this->router->generate('career'),
		];

		$menus['vereniging'] = [
			'label' => __('Association'),
			'submenu' => [
				[
					'url' => $this->router->generate('committees', ['commissie' => 'board']),
					'label' => __('Board')
				],
				[
					'url' => $this->router->generate('boards'),
					'label' => __('Former Boards')
				],
				[
					'url' => $this->router->generate('committees'),
					'label' => __('Committees')
				],
				[
					'url' => $this->router->generate('clubs'),
					'label' => __('Clubs')
				],
				[
					'url' => $this->router->generate('page', ['id' => 28]),
					'label' => __('Sister unions')
				],
				[
					'url' => $this->router->generate('page', ['id' => 18]),
					'label' => __('Become a member/contributor')
				],
				[
				  'label' => __('Well-being'),
				  'url' => $this->router->generate('page', ['id' => 213]),
				],
			]
		];

		$menus['contact'] = [
			'label' => __('Contact'),
			'url' => $this->router->generate('contact'),
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
					'label' => __('Members'),
					'url' => $this->router->generate('almanak'),
					'icon' => [
						'fa' => 'fas fa-users',
						'color' => 'cover',
					],
				],
				[
					'label' => __('Forum'),
					'url' => $this->router->generate('forum'),
					'icon' => [
						'fa' => 'fas fa-comments',
						'color' => 'cover',
					],
				],
				[
					'label' => __('Sticker map'),
					'url' => $this->router->generate('stickers'),
					'icon' => [
						'fa' => 'fas fa-map-marked-alt',
						'color' => 'cover',
					],
				],
			]
		];

		$tools['external'] = [
			'label' => __('Tools'),
			'items' => [
				[
					'label' => __('Wiki'),
					'url' => 'https://wiki.svcover.nl/',
					'target' => '_blank',
					'icon' => [
						'fa' => 'fas fa-graduation-cap',
						'color' => 'cover',
					],
				],
				[
					'label' => __('Documents & Templates'),
					'url' => 'https://sd.svcover.nl/',
					'target' => '_blank',
					'icon' => [
						'img' => '/images/applications/sd.png',
					],
				],
				[
					'label' => __('Merchandise'),
					'url' => 'https://merchandise.svcover.nl/',
					'target' => '_blank', 
					'icon' => [
						'fa' => 'fas fa-tshirt',
						'color' => 'cover',
					],
				],
				[
					'label' => __('Exams & Summaries'),
					'url' => 'https://studysupport.svcover.nl/',
					'target' => '_blank',
					'icon' => [
						'fa' => 'fas fa-book',
						'color' => 'cover',
					],
				],
				[
					'label' => __('Tutoring'),
					'url' => 'https://tutoring.svcover.nl/',
					'target' => '_blank',
					'icon' => [
						'img' => '/images/applications/tutoring.svg',
					],
				],
				[
					'label' => __('Submit an Idea'),
					'url' => 'https://idea.svcover.nl/',
					'target' => '_blank',
					'icon' => [
						'img' => '/images/applications/idea.svg',
					],
				],
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
					'fa' => 'fas fa-print',
					'color' => 'cover',
				],
				'url' => 'https://myprint.svcover.nl/',
				'label' => __('Printer'),
				'target' => '_blank',
				'title' => __("Print documents on Cover's printer")
			];

			$tools['external']['items'][] = [
				'icon' => [
					'img' => '/images/applications/reclaim.svg',
				],
				'url' => 'https://reclaim.svcover.nl/',
				'label' => __('Reclaim'),
				'target' => '_blank',
				'title' => __('Claim your expenses.')
			];

			$tools['external']['items'][] = [
				'label' => __('Webmail'),
				'title' => __('Webmail for Cover email accounts.'),
				'url' => 'https://webmail.svcover.nl/',
				'target' => '_blank',
				'icon' => [
					'img' => '/images/applications/mail.svg',
				],
			];

			$tools['admin']['items'][] = [
				'label' => __('Mailing lists'),
				'title' => __('Manage your committee\'s mailing lists.'),
				'url' => $this->router->generate('mailing_lists'),
				'icon' => [
					'fa' => 'fas fa-mail-bulk',
					'color' => 'dark',
					'icon_color' => 'light'
				],
			];

			$tools['admin']['items'][] = [
				'url' => $this->router->generate('signup'),
				'label' => __('Forms'),
				'title' => __('Manage your committee\'s sign-up forms.'),
				'icon' => [
					'fa' => 'fas fa-list-alt',
					'color' => 'dark',
					'icon_color' => 'light'
				],
			];
		}

		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) ||
			get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR) ||
			get_identity()->member_in_committee(COMMISSIE_EASY)) {
			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-file-alt',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => $this->router->generate('page.list'),
				'label' => __('Pages'),
				'title' => __('View and manage pages.')
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
				'url' => $this->router->generate('join', ['view' => 'pending-confirmation']),
				'label' => __('Pending registrations'),
				'title' => __('People who signed up for Cover, but did not yet confirm their email address.')
			];
		}

		if (get_identity() -> member_in_committee(COMMISSIE_BESTUUR) ||
			get_identity() -> member_in_committee(COMMISSIE_KANDIBESTUUR)) {
			$tools['admin']['items'][] = [
				'label' => __('Active members'),
				'title' => __('All active committee members according to the website.'),
				'url' => $this->router->generate('committee_members'),
				'icon' => [
					'fa' => 'fas fa-user-friends',
					'color' => 'dark',
					'icon_color' => 'light'
				],
			];
			$tools['admin']['items'][] = [
				'icon' => [
					'fa' => 'fas fa-building',
					'color' => 'dark',
					'icon_color' => 'light'
				],
				'url' => $this->router->generate('partners'),
				'label' => __('Partners'),
				'title' => __('All partner profiles and banners.')
			];
		}

		
		if (get_identity()->member_in_committee(COMMISSIE_EASY)) {
			$tools['admin']['items'][] = [
				'label' => __('Device sessions'),
				'title' => __('Manage device sessions.'),
				'url' => $this->router->generate('device_sessions'),
				'icon' => [
					'fa' => 'fas fa-desktop',
					'color' => 'dark',
					'icon_color' => 'light'
				],
			];
			$tools['admin']['items'][] = [
				'label' => __('Settings'),
				'title' => __('Manage a few of the website\'s settings.'),
				'url' => $this->router->generate('settings'),
				'icon' => [
					'fa' => 'fas fa-cog',
					'color' => 'dark',
					'icon_color' => 'light'
				],
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
