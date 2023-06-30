<?php
namespace App\Controller;

use App\Form\Type\CommitteeIdType;
use App\Form\Type\MemberIdType;
use App\Validator\Member;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

require_once 'src/framework/member.php';
require_once 'src/framework/controllers/Controller.php';

class SessionsController extends \Controller
{
	protected $view_name = 'sessions';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelSession');

		parent::__construct($request, $router);
	}

	protected function run_view_overrides()
	{
		if (!(get_identity() instanceof \ImpersonatingIdentityProvider))
			throw new \UnauthorizedException();

		$referrer = $this->get_parameter('referrer', $this->generate_url('homepage'));
		$data = [
			'referrer' => $referrer,
			'override_committees' => get_identity()->get_override_committees() !== null,
			'override_committee_ids' => get_identity()->get_override_committees() ?? [],
			'override_member' => get_identity()->get_override_member() !== null,
			'override_member_id' => get_identity()->get_override_member() ? get_identity()->get_override_member()->get_id() : null,
		];

		$form = $this->createFormBuilder($data)
			->add('override_member', CheckboxType::class, [
				'label' => __('Override member'),
				'required' => false,
			])
			->add('override_member_id', MemberIdType::class, [
				'label' => __('Member'),
				'required' => false,
				'constraints' => [
					new Member(),
				],
			])
			->add('override_committees', CheckboxType::class, [
				'label' => __('Override committee memberships'),
				'required' => false,
			])
			->add('override_committee_ids', CommitteeIdType::class, [
				'required' => false,
				'show_all' => true,
				'show_own' => false,
				'multiple' => true,
				'expanded' => true,
				'attr' => [
					'class' => 'chips',
				],
			])
			->add('referrer', HiddenType::class, [
				'required' => false,
			])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			if ($form['override_member']->getData() && !empty($form['override_member_id']->getData())) {
				$member_model = get_model('DataModelMember');
				$override_member = $member_model->get_iter($form['override_member_id']->getData());
				get_identity()->override_member($override_member);				
			} else {
				get_identity()->reset_member();
			}

			if ($form['override_committees']->getData())
				get_identity()->override_committees($form['override_committee_ids']->getData());
			else
				get_identity()->reset_committees();

			return $this->view->redirect($form['referrer']->getData() ?? $this->generate_url('sessions', ['view' => 'overrides']));
		}

		return $this->view->render('overrides.twig', [
			'referrer' => $referrer,
			'committees' => get_model('DataModelCommissie')->get(null, true),
			'form' => $form->createView(),
		]);
	}

	protected function run_view_sessions()
	{
		// TODO: is this view still used?
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException('You need to login to manage your sessions');

		return $this->view->redirect($this->generate_url('profile', ['view' => 'sessions', 'lid' => get_identity()->get('id')]));
	}

	protected function run_view_delete()
	{
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException('You need to log in to manage your sessions');

		$member = get_identity()->member();

		$session_id = $this->get_parameter('id');

		if (empty($session_id))
			throw new NotFoundException('Delete requires an session id, but none was specified');

		$session = $this->model->get_iter($session_id);

		$form = $this->createFormBuilder($session, ['csrf_token_id' => 'session_delete_' . $session['id']])
			->add('submit', SubmitType::class, ['label' => 'Delete'])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			// Make sure we can only delete our own sessions
			if ($session && $session->get('member_id') == $member->get_id())
				$this->model->delete($session);

			// Extra warning after we end the current session of the user
			if ($session && $session->get_id() === get_auth()->get_session()->get_id())
				$_SESSION['alert'] = __('Your session has been ended. You will need to log in again.');
		}

		$referrer = $this->get_parameter(
			'referrer',
			$this->generate_url('profile', ['view' => 'sessions', 'lid' => $member->get_id()])
		);
		return $this->view->redirect($referrer);
	}

	protected function run_view_login()
	{
		$referrer = $this->get_parameter('referrer', $this->generate_url('homepage'));

		$referrer_host = parse_url($referrer, PHP_URL_HOST);
		if ($referrer_host && !is_same_domain($referrer_host, $_SERVER['HTTP_HOST'], 3))
			$external_domain = parse_url($referrer, PHP_URL_HOST);
		else
			$external_domain = null;

		// Prevent returning to the logout link
		if ($external_domain === null && in_array($referrer, ['/sessions.php?view=logout', $this->generate_url('logout'), $this->generate_url('sessions', ['view' => 'logout'])]))
			$referrer = null;

		$data = [
			'referrer' => $referrer,
			'remember' => true,
		];

		$form = $this->createFormBuilder($data)
			->add('email', EmailType::class, [
				'label' => __('Email'),
				'constraints' => [new Assert\NotBlank(), new Assert\Email()],
				'attr' => [
					'placeholder' => __('Email'),
				],
			])->add('password', PasswordType::class, [
				'label' => __('Password'),
				'attr' => [
					'placeholder' => __('Password'),
				],
			])
			->add('remember', CheckboxType::class, [
				'label' => __('Keep me logged in'),
				'required' => false,
			])
			->add('referrer', HiddenType::class, [
				'required' => false,
			])
			->add('submit', SubmitType::class, [
				'label' => __('Log in'),
			])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			$referrer = $form['referrer']->getData();
			try {
				if (get_auth()->login($form['email']->getData(), $form['password']->getData(), $form['remember']->getData(), $_SERVER['HTTP_USER_AGENT'] ?: null)) {
					// User can apparently login, so invalidate all their password reset tokens
					try {
						$password_reset_model = get_model('DataModelPasswordResetToken');
						$password_reset_model->invalidate_all(get_identity()->member());
					} catch (\Exception $e) {
						throw $e;
						sentry_report_exception($e);
					}

					return $this->view->redirect($referrer ?: $this->generate_url('homepage'), false, ALLOW_SUBDOMAINS);
				} else {
					$form->addError(new FormError(__('Wrong combination of e-mail address and password')));
				}
			} catch (\InactiveMemberException $e) {
				return $this->view->render('inactive.twig');
			}
		}

		return $this->view->render('login.twig', [
			'form' => $form->createView(),
			'referrer' => $referrer,
			'external_domain' => $external_domain,
		]);
	}

	protected function run_view_logout()
	{
		if (get_auth()->logged_in())
			get_auth()->logout();

		if (isset($_GET['referrer']))
			return $this->view->redirect($_GET['referrer'], false, ALLOW_SUBDOMAINS);
		else
			return $this->view->render('logout.twig');
	}

	function run_impl()
	{
		switch ($this->get_parameter('view')) {
			case 'sessions':
				return $this->run_view_sessions();

			case 'delete':
				return $this->run_view_delete();

			case 'overrides':
				return $this->run_view_overrides();

			case 'login':
				return $this->run_view_login();

			case 'logout':
				return $this->run_view_logout();

			default:
				return get_auth()->logged_in()
					? $this->run_view_sessions()
					: $this->run_view_login();
		}
	}
}
