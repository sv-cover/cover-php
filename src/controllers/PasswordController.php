<?php
namespace App\Controller;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

require_once 'src/framework/controllers/Controller.php';

class PasswordController extends \Controller
{
	protected $view_name = 'password';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPasswordResetToken');

		$this->member_model = get_model('DataModelMember');

		parent::__construct($request, $router);
	}

	public function validate_password($value, ExecutionContextInterface $context, $payload, $member)
	{
		$effective_password = str_ireplace([$member['voornaam'],$member['achternaam'],'cover','password'], '', $value);

		// Short passwords, or very common passwords, are stupid.
		if (strlen($effective_password) < 6)
			$context->buildViolation(__('Your password is too short or too predictable. Try to make it longer and with more different characters.'))
				->atPath('password')
				->addViolation();
	}

	protected function run_reset()
	{
		try {
			$token = $this->model->get_iter($_GET['reset_token']);
		} catch (\DataIterNotFoundException $e) {
			return $this->run_request();
		}

		$form = $this->createFormBuilder()
			->add('password', RepeatedType::class, [
				'type' => PasswordType::class,
				'invalid_message' => __('The two passwords are not the same.'),
				'required' => true,
				'first_options'  => ['label' => 'New Password'],
				'second_options' => ['label' => 'Repeat Password'],
				'constraints' => [
					new Assert\Callback(function ($value, ExecutionContextInterface $context, $payload) use ($token) {
						return $this->validate_password($value, $context, $payload, $token['member']);
					}),
				],
			])
			->add('submit', SubmitType::class, [
				'label' => __('Change password'),
			])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			$this->member_model->set_password($token['member'], $form['password']->getData());

			$this->model->invalidate_all($token['member']);

			return $this->view->render('reset_processed.twig');
		}

		return $this->view->render('reset_form.twig', [
			'form' => $form->createView(),
		]);
	}

	protected function run_request()
	{
		$form = $this->createFormBuilder()
			->add('email', EmailType::class, [
				'label' => __('Email'),
				'constraints' => [new Assert\NotBlank(), new Assert\Email()],
			])
			->add('submit', SubmitType::class, [
				'label' => __('Reset password'),
			])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			try {
				$member = $this->member_model->get_from_email($form['email']->getData());

				$token = $this->model->create_token_for_member($member);

				$email = parse_email_object("password_reset_en.txt", [
					'naam' => $member['voornaam'],
					'link' => $token['link']
				]);
				$email->send($member['email']);
			} catch (\DataIterNotFoundException $e) {
				// Do nothing, we don't want to give membership status based on email
			}
			return $this->view->render('request_processed.twig', [
				'email' => $form['email']->getData(),
			]);
		}

		return $this->view->render('request_form.twig', [
			'form' => $form->createView(),
		]);
	}

	protected function run_impl()
	{
		if (isset($_GET['reset_token']))
			return $this->run_reset();
		else
			return $this->run_request();	
	}
}
