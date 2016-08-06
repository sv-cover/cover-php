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

		if (member_in_commissie(COMMISSIE_BESTUUR) ||
			member_in_commissie(COMMISSIE_KANDIBESTUUR) ||
			member_in_commissie(COMMISSIE_EASY)) {
			$menus['admin'] = ['label' => __('Beheer'), 'submenu' => []];
		
			$menus['admin']['submenu'][] = [
				'url' => 'show.php?show_new',
				'label' => __('Pagina maken')
			];

			if (member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
				$menus['admin']['submenu'][] = [
					'url' => 'agenda.php?agenda_moderate',
					'label' => __('Agenda')
				];

				$menus['admin']['submenu'][] = [
					'url' => 'actieveleden.php',
					'label' => __('Actieve leden')
				];

				$menus['admin']['submenu'][] = [
					'url' => 'forum.php?admin=forums',
					'label' => 'Forum'
				];

				$menus['admin']['submenu'][] = [
					'url' => 'nieuwlid.php',
					'label' => __('Leden toevoegen')
				];
			}
			
			if (member_in_commissie(COMMISSIE_EASY)) {
				$menus['admin']['submenu'][] = [
					'url' => 'settings.php',
					'label' => __('Instellingen')
				];
			}
		}

		$menus['vereniging'] = [
			'label' => __('Vereniging'),
			'submenu' => [
				[
					'url' => 'commissies.php?commissie=board',
					'label' => __('Bestuur')
				],
				[
					'url' => 'besturen.php',
					'label' => __('Vorige besturen')
				],
				[
					'url' => 'commissies.php',
					'label' => __('Commissies')
				],
				[
					'url' => 'workinggroups.php',
					'label' => __('Werkgroepen')
				],
				[
					'url' => 'show.php?id=28',
					'label' => __('Zusterverenigingen')
				],
				[
					'url' => 'show.php?id=18',
					'label' => __('Lid/donateur worden')
				],
				[
					'url' => 'show.php?id=30',
					'label' => __('Documenten'),
				],
				[
					'url' => 'weblog.php',
					'label' => __('Weblog')
				]
			]
		];

		$menus['leden'] = [
			'label' => __('Leden'),
			'submenu' => [
				['url' => 'almanak.php', 'label' => __('Almanak')],
				['url' => 'mailinglijsten.php', 'label' => __('Mailinglijsten')],
				['url' => 'https://wiki.svcover.nl/', 'target' => '_blank', 'label' => __('Wiki')],
				['url' => 'https://sd.svcover.nl/', 'target' => '_blank', 'label' => __('Standaardocumenten')],
				['url' => 'stickers.php', 'label' => __('Stickerkaart')],
				['url' => 'http://www.shitbestellen.nl', 'target' => '_blank', 'label' => __('Merchandise')],
				['url' => 'dreamspark.php', 'label' => __('Dreamspark')]
			]
		];

		$menus['bedrijven'] = [
			'label' => __('Bedrijven'),
			'submenu' => [
				['url' => 'show.php?id=51', 'label' => __('Bedrijfsprofielen')],
				['url' => 'show.php?id=54', 'label' => __('Vacatures')],
				['url' => 'show.php?id=31', 'label' => __('Stages/afstudeerplaatsen')],
				['url' => 'show.php?id=56', 'label' => __('Sponsormogelijkheden')]
			]
		];

		$menus['forum'] = [
			'url' => 'forum.php',
			'label' => __('Forum')
		];

		$menus['fotoboek'] = [
			'url' => 'fotoboek.php',
			'label' => __('Foto\'s')
		];

		$menus['studie'] = [
			'label' => __('Studie'),
			'submenu' => [
				['url' => 'show.php?id=23', 'label' => __('K.I.')],
				['url' => 'show.php?id=41', 'label' => __('Informatica')],
				['url' => 'show.php?id=24', 'label' => __('Alumni')],
				['url' => 'boeken.php', 'label' => __('Boeken bestellen')],
				['url' => 'show.php?id=27', 'label' => __('Info voor studenten')],
				['url' => 'http://studieondersteuning.svcover.nl/', 'target' => '_blank', 'label' => __('Tentamens & Samenvattingen')]
			]
		];

		$menus['contact'] = [
			'label' => __('Contact'),
			'url' => 'show.php?id=17'
		];

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
}