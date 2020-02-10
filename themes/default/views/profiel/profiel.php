<?php

require_once 'include/facebook.php';

use JeroenDesloovere\VCard\VCard;

function empty_to_http_formatter($value) {
	if (!$value)
		return 'http://';
	else
		return $value;
}


class ProfielView extends View
{
	// public function stylesheets()
	// {
	// 	return array_merge(parent::stylesheets(), [
	// 		get_theme_data('styles/profiel.css')
	// 	]);
	// }

	public function tabs(DataIterMember $iter)
	{
		return [
			'public' => [
				'visible' => true,
				'label' => member_full_name($iter, BE_PERSONAL),
				'icon' => 'fas fa-globe'
				// 'body' => function () use ($model, $iter, $personal_fields) {
				// 	$this->render_partial('public', compact('model', 'iter', 'personal_fields'));
				// }
			],
			'personal' => [
				'visible' => $this->is_current_member($iter),
				'label' => __('Personal')
				// 'body' => function () use ($model, $iter, $errors, $personal_fields) {
				// 	$this->render_partial('personal', compact('model', 'iter', 'errors', 'personal_fields'));
				// }
			],
			'profile' => [
				'visible' => $this->member_write_permission($iter),
				'label' => __('Profile')
				// 'body' => function () use ($model, $iter, $errors) {
				// 	$this->render_partial('photo', compact('iter', 'errors'));
				// 	$this->render_partial('profile', compact('model', 'iter', 'errors'));
				// 	$this->render_partial('password', compact('iter', 'errors'));
				// }
			],
			'privacy' => [
				'visible' => $this->member_write_permission($iter),
				'label' => __('Privacy')
				// 'body' => function () use ($model, $iter) {
				// 	$this->render_partial('privacy', compact('model', 'iter'));
				// }
			],
			'mailing_lists' => [
				'visible' => $this->member_write_permission($iter),
				'label' => __('Mailing lists')
				// 'body' => function () use ($iter) {
				// 	$this->render_partial('mailinglists', compact('iter'));
				// }
			],
			'sessions' => [
				'visible' => $this->is_current_member($iter),
				'label' => __('Sessions')
				// 'body' => function () use ($iter) {
				// 	$this->render_partial('sessions', compact('iter'));
				// }
			],
			'facebook' => [
				'visible' => $this->is_current_member($iter) && get_config_value('enable_facebook_rsvp', false),
				'label' => __('Facebook')
				// 'body' => function () use ($iter) {
				// 	$this->render_partial('facebook', compact('iter'));
				// }
			],
			'kast' => [
				'visible' => $this->is_current_member($iter),
				'label' => __('Consumptions')
				// 'body' => function () use ($iter) {
				// 	$this->render_partial('kast', compact('iter'));
				// }
			],
			'incassomatic' => [
				'visible' => $this->is_current_member($iter),
				'label' => __('Direct debits')
				// 'body' => function () use ($iter) {
				// 	$this->render_partial('incassomatic', compact('iter'));
				// }
			]
		];
	}

	public function personal_fields()
	{
		return [
			[	
				'label' => __('Name'),
				'name' => 'full_name',
				'read_only' => true
			],
			[
				'label' => __('Birthdate'),
				'name' => 'geboortedatum',
				'read_only' => true
			],
			[
				'label' => __('Starting year'),
				'name' => 'beginjaar',
				'read_only' => true
			],
			[
				'label' => __('Address'),
				'name' => 'adres'
			],
			[
				'label' => __('Zipcode'),
				'name' => 'postcode'
			],
			[
				'label' => __('Place of residence'),
				'name' => 'woonplaats'
			],
			[
				'label' => __('Phone number'),
				'name' => 'telefoonnummer'
			],
			[
				'label' => __('E-mail address'),
				'name' => 'email'
			]
		];
	}

	public function render_public_tab(DataIterMember $iter)
	{
		$can_download_vcard = get_identity()->is_member();

		$is_current_user = get_identity()->get('id') == $iter->get('id');

		$model = get_model('DataModelCommissie');

		$committees = $model->get_for_member($iter);

		return $this->render('public_tab.twig', compact('iter', 'is_current_user', 'can_download_vcard', 'committees'));
	}

	public function render_personal_tab(DataIterMember $iter, $error_message = null, array $errors = [])
	{
		return $this->render('personal_tab.twig', compact('iter', 'error_message', 'errors'));
	}

	public function render_profile_tab(DataIterMember $iter, $error_message = null, array $errors = [])
	{
		$current_password_required = !get_identity()->member_in_committee(COMMISSIE_BESTUUR)
		                          && !get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);

		return $this->render('profile_tab.twig', compact('iter', 'error_message', 'errors', 'current_password_required'));
	}

	public function render_privacy_tab(DataIterMember $iter, $error_message = null, array $errors = [])
	{
		$fields = [];

		$labels = [];

		foreach ($this->personal_fields() as $field)
			$labels[$field['name']] = $field['label'];

		// Stupid aliasses
		$labels['naam'] = $labels['full_name'];
		$labels['foto'] = __('Photo');

		foreach ($this->controller->model()->get_privacy() as $field => $nr)
			$fields[] = [
				'label' => $labels[$field] ?? $field,
				'name' => 'privacy_' . $nr,
				'data' => ['privacy_' . $nr => ($iter['privacy'] >> ($nr * 3)) & 7]
			];
		
		return $this->render('privacy_tab.twig', compact('iter', 'error_message', 'errors', 'fields'));
	}

	public function render_sessions_tab(DataIterMember $iter)
	{
		$model = get_model('DataModelSession');
		$sessions = $model->getActive($iter['id']);
		$sessions_view = View::byName('sessions');
		return $this->render('sessions_tab.twig', compact('iter', 'sessions', 'sessions_view'));
	}

	public function render_kast_tab(DataIterMember $iter)
	{
		require_once 'include/kast.php';

		try {
			$kast_api = get_kast();
			$status = $kast_api->getStatus($iter['id']);
			$history = $kast_api->getHistory($iter['id'], 20);
			return $this->render('kast_tab.twig', compact('iter', 'status', 'history'));
		} catch (Exception $exception) {
			return $this->render('kast_tab_exception.twig', compact('iter', 'exception'));
		}
	}

	public function render_incassomatic_tab(DataIterMember $iter)
	{
		require_once 'include/incassomatic.php';

		$treasurer = get_model('DataModelCommissie')->get_lid_for_functie(COMMISSIE_BESTUUR, 'treasurer');
		$treasurer_link = sprintf('<a href="profiel.php?lid=%d">%s</a>',
			$treasurer['id'], markup_format_text(member_full_name($treasurer)));

		try {
			$incasso_api = \incassomatic\shared_instance();
			$contracts = $incasso_api->getContracts($iter);

			// Only show valid contracts
			$contract = current(array_filter($contracts, function($contract) { return $contract->is_geldig; }));

			if (!$contract)
				return $this->render('incassomatic_tab_no_contract.twig', compact('iter', 'treasurer_link'));
			
			$debits = $incasso_api->getDebits($iter, 15);

			$debits_per_batch = array_group_by($debits, function($debit) { return $debit->batch_id; });

			return $this->render('incassomatic_tab.twig', compact('iter', 'contract', 'treasurer_link', 'debits_per_batch'));
		} catch (Exception $exception) {
			sentry_report_exception($exception);
			return $this->render('incassomatic_tab_exception.twig', compact('iter', 'exception'));
		}
	}

	public function render_vcard(DataIterMember $member)
	{
		$card = new VCard();

		// Macro for checking whether a field is not private.
		$is_visible = function($field) use ($member) {
			return in_array($this->controller->model()->get_privacy_for_field($member, $field),
				[DataModelMember::VISIBLE_TO_EVERYONE, DataModelMember::VISIBLE_TO_MEMBERS]);
		};
		
		if ($is_visible('naam'))
			$card->addName($member['achternaam'], $member['voornaam'], $member['tussenvoegsel']);

		if ($is_visible('email'))
			$card->addEmail($member['email']);

		if ($is_visible('telefoonnummer'))
			$card->addPhoneNumber($member['telefoonnummer'], 'PREF;HOME');
		
		if ($is_visible('adres') || $is_visible('postcode') || $is_visible('woonplaats'))
			$card->addAddress(null, null,
				$is_visible('adres') ? $member['adres'] : null,
				$is_visible('woonplaats') ? $member['woonplaats'] : null,
				null,
				$is_visible('postcode') ? $member['postcode'] : null,
				null);

		if ($is_visible('geboortedatum'))
			$card->addBirthday($member['geboortedatum']);

		// For some weird reason is 'http://' the default value for members their homepage.
		if (!empty($member['homepage']) && $member['homepage'] != 'http://')
			$card->addURL($member['homepage']);

		// Only add a thumbnail of the photo if the member has one, and it isn't hidden.
		if ($is_visible('foto') && $this->controller->model()->has_picture($member)) {
			$fout = null;

			$photo = $this->controller->model()->get_photo_stream($member);

			$imagick = new \Imagick();
			$imagick->readImageFile($photo['foto']);

			apply_image_orientation($imagick);

			strip_exif_data($imagick);
			
			$y = 0.05 * $imagick->getImageHeight();
			$size = min($imagick->getImageWidth(), $imagick->getImageHeight());
			
			if ($y + $size > $imagick->getImageHeight())
				$y = 0;

			$imagick->cropImage($size, $size, 0, $y);
			$imagick->scaleImage(96, 0);

			$imagick->setImageFormat('jpeg');

			$fout = fopen('php://memory', 'wb+');
			stream_filter_append($fout, 'convert.base64-encode', STREAM_FILTER_WRITE);

			$imagick->writeImageFile($fout);
			$imagick->destroy();

			rewind($fout);

			// Use reflection to get to the private addMedia method. Only addPhoto is public, but that
			// doesn't accept a stream and I'm not in the mood to write a temporary file to disk.
			$vCardClass = new \ReflectionClass($card);
			$vCard_addMedia = $vCardClass->getMethod('setProperty');
			$vCard_addMedia->setAccessible(true);
			$vCard_addMedia->invoke($card, 'photo', 'PHOTO;ENCODING=b;TYPE=JPEG', stream_get_contents($fout));

			fclose($fout);
		}

		if (!is_array($card->getProperties()))
			throw new NotFoundException('This member has no public fields in their profile.');
		
		$card->download();
		
		return null;
	}

	public function render_confirm_email($success)
	{
		return $this->render('confirm_email.twig', compact('success'));
	}

	function is_current_member(DataIterMember $iter)
	{
		return get_identity()->get('id') == $iter->get_id();
	}

	function member_write_permission(DataIterMember $iter)
	{
		return $this->is_current_member($iter)
			|| get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function format_member_data(DataIterMember $iter, $field)
	{
		switch ($field) {
			case 'beginjaar':
				return sprintf('<a href="almanak.php?search_year=%d">%1$d</a>', $iter['beginjaar']);
			case 'adres':
				return sprintf('<a href="%s" target="_blank">%s</a>',
					'https://www.google.nl/maps/search/' . urlencode($iter['adres'] . ' ' . $iter['woonplaats']) . '/',
					markup_format_text($iter['adres']));
			case 'email':
				return sprintf('<a href="mailto:%s">%s</a>',
					urlencode($iter['email']),
					markup_format_text($iter['email']));
			case 'telefoonnummer':
				try {
					$phone_util = \libphonenumber\PhoneNumberUtil::getInstance();
					$phone_number = $phone_util->parse($iter[$field], 'NL');
					return sprintf('<a href="tel:%s">%s</a>',
						$phone_util->format($phone_number, \libphonenumber\PhoneNumberFormat::E164),
						$phone_util->format($phone_number, \libphonenumber\PhoneNumberFormat::INTERNATIONAL));
				} catch (\libphonenumber\NumberParseException $e) {
					return markup_format_text($iter[$field]);
				}
			default:
				return markup_format_text($iter[$field]);
		}
	}

	public function member_type_to_string($type)
	{
		$mapping = [
			MEMBER_STATUS_LID => __('Member'),
			MEMBER_STATUS_LID_AF => __('Previously a member'),
			MEMBER_STATUS_ERELID => __('Honorary Member'),
			MEMBER_STATUS_DONATEUR => __('Donor'),
			MEMBER_STATUS_UNCONFIRMED => __('To be processed')
		];

		return $mapping[$type];
	}

	public function hostname($url)
	{
		return parse_url($url, PHP_URL_HOST);
	}
}
