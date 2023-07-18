<?php
namespace App\Controller;

require_once 'src/framework/form.php';
require_once 'src/framework/member.php';
require_once 'src/framework/login.php';
require_once 'src/services/facebook.php';
require_once 'src/services/secretary.php';
require_once 'src/framework/controllers/Controller.php';
require_once 'src/framework/email.php';

use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class ProfileController extends \Controller
{
	protected $view_name = 'profile';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelMember');

		$this->policy = get_policy($this->model);

		$this->sizes = [
			'adres' => 255,
			'postcode' => 7,
			'woonplaats' => 255,
			'email' => 255,
			'telefoonnummer' => 20,
			'avatar' => 100,
			'homepage' => 255,
			'nick' => 50,
			'onderschrift' => 200
		];

		$this->required = [
			'adres',
			'postcode',
			'woonplaats',
			'email'
		];

		parent::__construct($request, $router);
	}
	
	public function validate_password($value, ExecutionContextInterface $context, $payload, $member)
	{
		/**
		 * Password validator. Same as in PasswordController
		 */
		$effective_password = str_ireplace([$member['voornaam'],$member['achternaam'],'cover','password'], '', $value);

		// Short passwords, or very common passwords, are stupid.
		if (strlen($effective_password) < 6)
			$context->buildViolation(__('Your password is too short or too predictable. Try to make it longer and with more different characters.'))
				->atPath('password')
				->addViolation();
	}

	public function _check_size($name, $value)
	{
		if ($value === null || !isset($this->sizes[$name]))
			return $value;

		if (strlen(trim($value)) === 0 && in_array($name, $this->required))
			return false;

		if (strlen(trim($value)) > $this->sizes[$name])
			return false;
		
		return trim($value);
	}

	public function _check_geboortedatum($name, $value)
	{
		if (!preg_match('/([0-9]+)(-|\/)([0-9]+)(-|\/)([0-9]+)/', $value, $matches))
			return false;
		
		return $matches[5] . '-' . $matches[3] . '-' . $matches[1];
	}

	public function _check_type($name, $value)
	{
		return $value !== null && is_numeric($value) && $value >=1 && $value <= 4;
	}

	public function _check_language($name, $value)
	{
		return in_array($value, array('nl', 'en')) ? $value : false;
	}

	public function _check_phone($name, $value)
	{
		try {
			$phone_util = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone_number = $phone_util->parse($value, 'NL');
			return $phone_util->isValidNumber($phone_number)
				? $phone_util->format($phone_number, \libphonenumber\PhoneNumberFormat::E164)
				: false;
		} catch (\libphonenumber\NumberParseException $e) {
			return false;
		}
	}

	public function _check_email($name, $value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	private function _report_changes_upstream(\DataIterMember $iter)
	{
		// Inform the board that member info has been changed.
		$subject = "Member details updated";
		$body = sprintf("%s updated their member details:", member_full_name($iter, IGNORE_PRIVACY)) . "\n\n";
		
		foreach ($iter->secretary_changed_values() as $field => $value)
			$body .= sprintf("%s:\t%s\n", $field, $value ?? "<deleted>");
			
		mail('administratie@svcover.nl', $subject, $body, "From: Study Association Cover <noreply@svcover.nl>\r\nContent-Type: text/plain; charset=UTF-8");
		mail('secretaris@svcover.nl', $subject, sprintf("%s updated their member details:\n\nYou can see the changes in sectary or in the administratie@svcover.nl mailbox", member_full_name($iter, IGNORE_PRIVACY)), "From: Study Association Cover <noreply@svcover.nl>\r\nContent-Type: text/plain; charset=UTF-8");

		try {
			get_secretary()->updatePersonFromIterChanges($iter);
		} catch (\RuntimeException $e) {
			// Todo: replace this with a serious more general logging call
			error_log($e, 1, 'webcie@rug.nl', "From: webcie-cover-php@svcover.nl");
		}
	}

	protected function _get_personal_form(\DataIterMember $iter)
	{
		$form = $this->createFormBuilder($iter)
			->add('adres', TextType::class, [
				'label' => __('Address'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(['max' => 255]),
				],
			])
			->add('postcode', TextType::class, [
				'label' => __('Postal code'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(['max' => 7]),
				],
			])
			->add('woonplaats', TextType::class, [
				'label' => __('Town'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(['max' => 255]),
				],
			])
			->add('telefoonnummer', TelType::class, [
				'label' => __('Phone'),
				'constraints' => [
					new Assert\NotBlank(),
					new AssertPhoneNumber(['defaultRegion' => 'NL']),
					new Assert\Length(['max' => 20]),
				]
			])
			->add('email', EmailType::class, [
				'label' => __('Email'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Email(),
					new Assert\Length(['max' => 255]),
				],
				'setter' => function (\DataIterMember &$member, string $value, FormInterface $form) {
					// Prevent normal flow by doing nothing. Email requires special treatment.
				},
			])
			->add('iban', TextType::class, [
				'label' => __('IBAN'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Iban(),
				],
			])
			->add('bic', TextType::class, [
				'label' => __('BIC'),
				'required' => false,
				'constraints' => [
					new Assert\Bic(),
				],
				'help' => __("BIC is required if your IBAN does not start with 'NL'"), // This is never validated for better UX. Treasurer can always look it up.
			])
			->add('submit', SubmitType::class, ['label' => __('Save')])
			->getForm();
		$form->handleRequest($this->get_request());
		return $form;
	}

	protected function _get_profile_form(\DataIterMember $iter)
	{
		$form = get_form_factory()->createNamedBuilder('profile', FormType::class, $iter)
			->add('nick', TextType::class, [
				'label' => __('Nickname'),
				'required' => false,
				'constraints' => [
					new Assert\Length(['max' => 50]),
				]
			])
			->add('avatar', UrlType::class, [
				'label' => __('Avatar'),
				'required' => false,
				'default_protocol' => null, // if not, it renders as text type…
				'constraints' => [
					new Assert\Url(),
					new Assert\Length(['max' => 100]),
				],
				'attr' => [
					'placeholder' => 'https://',
				],
			])
			->add('homepage', UrlType::class, [
				'label' => __('Website'),
				'required' => false,
				'default_protocol' => null, // if not, it renders as text type…
				'constraints' => [
					new Assert\Url(),
					new Assert\Length(['max' => 255]),
				],
				'attr' => [
					'placeholder' => 'https://',
				],
			])
			->add('submit', SubmitType::class, ['label' => __('Save')])
			->getForm();
		$form->handleRequest($this->get_request());
		return $form;
	}

	protected function _get_password_form(\DataIterMember $iter)
	{
		$form = get_form_factory()->createNamedBuilder('password', FormType::class)
			->add('current', PasswordType::class, [
				'label' => __('Current Password'),
				'required' => true,
				'constraints' => [
					new Assert\Callback(function ($value, ExecutionContextInterface $context, $payload) use ($iter) {
						if (!$this->model->test_password($iter, $value))
							$context->buildViolation(__('That’s not your current password!'))
								->atPath('current')
								->addViolation();
					}),
				],
			])
			->add('password', RepeatedType::class, [
				'type' => PasswordType::class,
				'invalid_message' => __('The two passwords are not the same.'),
				'required' => true,
				'first_options'  => ['label' => 'New Password'],
				'second_options' => ['label' => 'Repeat'],
				'constraints' => [
					new Assert\Callback(function ($value, ExecutionContextInterface $context, $payload) use ($iter) {
						// Make sure new password is actually new. This is done here to make sure validate_password is implemented the same everywhere
						if ($this->model->test_password($iter, $value))
							$context->buildViolation(__('Your new password cannot be the same as your current password!'))
								->atPath('password')
								->addViolation();
						return $this->validate_password($value, $context, $payload, $iter);
					}),
				],
			])
			->add('submit', SubmitType::class, ['label' => __('Change password')])
			->getForm();
		$form->handleRequest($this->get_request());
		return $form;
	}

	// TODO: is public so it can be accessed from view. Make private after UI change
	public function _get_photo_form()
	{
		$form = $this->createFormBuilder()
			->add('photo', FileType::class, [
				'label' => __('Photo'),
				'constraints' => [
					new Assert\Image([
						'maxSize' => ini_get('upload_max_filesize'),
						'mimeTypes' => [
							'image/jpeg',
						],
						'mimeTypesMessage' => __('Please upload a valid JPEG-image.'),
						'sizeNotDetectedMessage' => __('The uploaded file doesn’t appear to be an image.'),
					])
				],
				'attr' => [
					'accept' => 'image/jpeg',
				],
			])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());
		return $form;
	}

	protected function _get_privacy_form(\DataIterMember $iter)
	{
		// Load labels
		$labels = [];

		foreach ($this->view->personal_fields() as $field)
			$labels[$field['name']] = $field['label'];

		// Stupid aliasses
		$labels['naam'] = $labels['full_name'];
		$labels['foto'] = __('Photo');

		// Build form
		$data = [];
		$builder = $this->createFormBuilder();

		foreach ($this->model()->get_privacy() as $field => $nr)
			$builder->add($field, ChoiceType::class, [
				'label' => $labels[$field] ?? $field,
				'choices'  => [
					__('Everyone') => \DataModelMember::VISIBLE_TO_EVERYONE,
					__('Members') => \DataModelMember::VISIBLE_TO_MEMBERS,
					__('Nobody') => \DataModelMember::VISIBLE_TO_NONE,
				],
				'expanded' => true,
				'attr' => [
					'class' => 'chips',
				],
				'data' => ($iter['privacy'] >> ($nr * 3)) & 7, // Not ideal, but neater than constructing something to pass to createFormBuilder
			]);

		$form = $builder
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		return $form;
	}

	public function run_personal(\DataIterMember $iter)
	{
		if (!$this->policy->user_can_update($iter))
			throw new \UnauthorizedException();

		$form = $this->_get_personal_form($iter);

		if ($form->isSubmitted() && $form->isValid()) {
			$updates = [];
			if ($iter->has_secretary_changes()) {
				$updates[] = 'other';
				$this->model->update($iter);
				$this->_report_changes_upstream($iter);
			}

			// If the email address has changed, add a confirmation.
			if ($form['email']->getData() != $iter['email']) {
				$updates[] = 'email';
				$token = get_model('DataModelEmailConfirmationToken')->create_token($iter, $form['email']->getData());

				$variables = [
					'naam' => member_first_name($iter, IGNORE_PRIVACY),
					'email' => $token['email'],
					'link' => $token['link']
				];

				// Send the confirmation to the new email address
				parse_email_object("profile_confirm_email.txt", $variables)->send($token['email']);
			}

			return $this->view->render('personal_tab_success.twig', [
				'iter' => $iter,
				'updates' => $updates,
			]);
		}

		return $this->view->render('personal_tab.twig', [
			'iter' => $iter,
			'form' => $form->createView()
		]);
	}

	public function run_profile(\DataIterMember $iter)
	{
		if (!$this->policy->user_can_update($iter))
			throw new \UnauthorizedException();

		$profile_form = $this->_get_profile_form($iter);

		if ($profile_form->isSubmitted() && $profile_form->isValid()) {
			$this->model->update($iter);
			return $this->view->redirect($this->generate_url('profile', ['view' => 'profile', 'lid' => $iter['id']]));
		}

		$password_form = $this->_get_password_form($iter);

		if ($password_form->isSubmitted() && $password_form->isValid()) {
			$this->model->set_password($iter, $password_form['password']->getData());
			$_SESSION['alert'] = __('Your password has been changed.');
			return $this->view->redirect($this->generate_url('profile', ['view' => 'profile', 'lid' => $iter['id']]));
		}

		$photo_form = $this->_get_photo_form();

		return $this->view->render('profile_tab.twig', [
			'iter' => $iter,
			'profile_form' => $profile_form->createView(),
			'photo_form' => $photo_form->createView(),
			'password_form' => $password_form->createView(),
		]);
	}
	
	public function run_privacy(\DataIterMember $iter)
	{
		if (!$this->policy->user_can_update($iter))
			throw new \UnauthorizedException();

		$form = $this->_get_privacy_form($iter);

		// Handle submission
		if ($form->isSubmitted() && $form->isValid()) {
			// Build privacy mask 
			$mask = 0;
			foreach ($this->model->get_privacy() as $field => $nr) {
				$value = $form[$field]->getData();
				$mask = $mask + ($value << ($nr * 3));
			}
			
			// Update settings
			$iter->set('privacy', $mask);
			$this->model->update($iter);
			
			return $this->view->redirect($this->generate_url('profile', ['view' => 'privacy', 'lid' => $iter['id']]));
		}

		return $this->view->render('privacy_tab.twig', [
			'iter' => $iter,
			'form' => $form->createView(),
		]);
	}

	protected function run_photo(\DataIterMember $iter)
	{
		// Only members themselves and the AC/DCee can change photos
		if (!$this->policy->user_can_update($iter)
			&& !get_identity()->member_in_committee(COMMISSIE_EASY))
			throw new \UnauthorizedException();


		$form = $this->_get_photo_form();
		if ($form->isSubmitted() && $form->isValid()) {
			$file = $form['photo']->getData();
			if (get_identity()->member_in_committee(COMMISSIE_EASY)) {
				$fh = fopen($file->getPathname(), 'rb');

				if (!$fh)
					throw new \RuntimeException(__('The uploaded file could not be opened.'));

				$this->model->set_photo($iter, $fh);

				fclose($fh);
			} else {
				$profile_link = $this->generate_url('profile', ['id' => $iter['id']], UrlGeneratorInterface::ABSOLUTE_URL);
				send_mail_with_attachment(
					'acdcee@svcover.nl',
					'New yearbook photo for ' . $iter['full_name'],
					"{$iter['full_name']} would like to use the attached photo as their new profile picture. Change it here: {$profile_link}",
					sprintf('Reply-to: %s <%s>', $iter['full_name'], $iter['email']),
					[$file->getClientOriginalName() => $file->getPathname()]);

				$_SESSION['alert'] = __('Your photo has been submitted. It may take a while before it will be updated.');
			}
		}

		return $this->view->redirect($this->generate_url('profile', ['view' => 'profile', 'lid' => $iter['id']]));
	}

	protected function run_facebook(\DataIterMember $iter)
	{
		if ($iter->get('id') != get_identity()->get('id'))
			throw new \UnauthorizedException();

		if ($this->_form_is_submitted('facebook', $iter))
		{
			if (get_post('facebook_action') == 'unlink')
				get_facebook()->destroySession();

			return $this->view->redirect($this->generate_url('profile', ['view' => 'facebook', 'lid' => $iter['id']]));
		}
		
		return $this->view->render_facebook_tab($iter);
	}

	public function run_export_vcard(\DataIterMember $member)
	{
		if (!get_identity()->is_member())
			throw new \UnauthorizedException('You need to log in to be able to export v-cards/');

		if (!$this->policy->user_can_read($member))
			throw new \UnauthorizedException('This member is no longer a member of Cover.');

		return $this->view->render_vcard($member);
	}

	public function run_public(\DataIterMember $member)
	{
		if (!$this->policy->user_can_read($member))
			throw new \UnauthorizedException('This person is no longer a member of Cover, which is why they no longer have a public profile.');

		return $this->view->render_public_tab($member);
	}

	public function run_mailing_lists(\DataIterMember $member)
	{

		if (!$this->policy->user_can_update($member))
			throw new \UnauthorizedException();

		$model = get_model('DataModelMailinglist');
		$mailing_lists = $model->get_for_member($member);
	
		$lists = array_filter($mailing_lists, function($list) {
			// return true;
			return get_policy($list)->user_can_subscribe($list);
			// TODO: should we show all list a person is subscribed to?
			// return get_policy($list)->user_can_subscribe($list) || $list['subscribed'];
		});

		return $this->view->render('mailing_lists_tab.twig', ['iter' => $member, 'mailing_lists' => $lists]);
	}

	public function run_sessions(\DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new \UnauthorizedException();

		$model = get_model('DataModelSession');

		return $this->view->render('sessions_tab.twig', [
			'iter' => $member,
			'sessions' => $model->getActive($member['id']),
			'sessions_view' => \View::byName('sessions'),
		]);
	}

	public function run_kast(\DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new \UnauthorizedException();

		return $this->view->render_kast_tab($member);
	}

	public function run_incassomatic(\DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new \UnauthorizedException();

		require_once 'src/services/incassomatic.php';

		try {
			$incasso_api = \incassomatic\shared_instance();
			$contract = $incasso_api->getCurrentContract($member);

			if (!$contract)
				return $this->view->redirect($this->generate_url('profile', [
					'view' => 'incassomatic_create_mandate',
					'lid' => $member['id']
				]));

			$debits = $incasso_api->getDebits($member, 15);
		} catch (\Exception|\Error $exception) {
			sentry_report_exception($exception);
			return $this->view->render('incassomatic_tab_exception.twig', [
				'iter' => $member,
				'exception' => $exception,
			]);
		}
		$debits_per_batch = array_group_by($debits, function($debit) { return $debit->batch_id; });

		return $this->view->render('incassomatic_tab.twig', [
			'iter' => $member,
			'contract' => $contract,
			// 'treasurer_link' => $treasurer_link,
			'debits_per_batch' => $debits_per_batch,
		]);
	}

	public function run_incassomatic_create_mandate(\DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new \UnauthorizedException();

		require_once 'src/services/incassomatic.php';

		try {			
			$incasso_api = \incassomatic\shared_instance();
			if ($incasso_api->getCurrentContract($member))
				return $this->view->redirect($this->generate_url('profile', [
					'view' => 'incassomatic',
					'lid' => $member['id']
				]));
		} catch (\Exception|\Error $exception) {
			sentry_report_exception($exception);
			return $this->view->render('incassomatic_tab_exception.twig', [
				'iter' => $member,
				'exception' => $exception,
			]);
		}

		$form = $this->createFormBuilder()
			->add('sepa_mandate', CheckboxType::class, [
				'label' => __('I hereby authorize Cover to automatically deduct the membership fee, costs for attending activities, and additional costs (e.g. food and drinks) from my bank account for the duration of my membership.'),
				'required' => true,
			])
			->add('submit', SubmitType::class, ['label' => 'Sign mandate'])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			$response = $incasso_api->createContract($member);
			return $this->view->redirect($this->generate_url('profile', [
				'view' => 'incassomatic',
				'lid' => $member['id']
			]));
		}

		try {
			$secretary_data = current(get_secretary()->findPerson($member->get_id()));
		} catch (\Exception|Error $exception) {
			sentry_report_exception($exception);
			$secretary_data = [];
		}

		return $this->view->render('incassomatic_tab_no_contract.twig', [
			'iter' => $member,
			'form' => $form->createView(),
			'secretary_data' => $secretary_data,
		]);
	}

	public function run_export_incassocontract(\DataIterMember $member)
	{
		if (!$this->policy->user_can_update($member))
			throw new \UnauthorizedException();

		require_once 'src/services/incassomatic.php';

		$incasso_api = \incassomatic\shared_instance();

		$fh = $incasso_api->getContractTemplatePDF($member);

		header('Content-Type: application/pdf');
		fpassthru($fh);
		fclose($fh);
	}

	public function run_confirm_email()
	{
		$model = get_model('DataModelEmailConfirmationToken');

		try {
			$token = $model->get_iter($_GET['token']);
		} catch (\Exception $e) {
			return $this->view->render_confirm_email(false);
		}

		// Update the member's email address
		$member = $token['member'];
		$old_email = $member['email'];
		$member['email'] = $token['email'];
		$this->model->update($member);

		// Report the changes to the secretary and to Secretary (the system...)
		$this->_report_changes_upstream($member);

		// Delete this and all other tokens for this user
		$model->invalidate_all($token['member']);

		return $this->view->render_confirm_email(true);
	}

	public function run_index()
	{
		return $this->view->redirect($this->generate_url('almanak'));
	}

	protected function run_impl()
	{
		$view = isset($_GET['view']) ? $_GET['view'] : 'public';
		
		if ($view == 'confirm_email')
			return $this->run_confirm_email(); // a bit of a special case: a method that does not need a DataIterMember :O

		if (isset($_GET['lid']))
			$iter = $this->model->get_iter($_GET['lid']);
		elseif (get_auth()->logged_in())
			$iter = get_identity()->member();
		else
			return $this->run_index();

		if (!method_exists($this, 'run_' . $view))
			throw new \NotFoundException("View '$view' not implemented by " . get_class($this));

		return call_user_func_array([$this, 'run_' . $view], [$iter]);
	}
}

