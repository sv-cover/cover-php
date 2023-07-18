<?php
namespace App\Controller;

require_once 'src/framework/controllers/Controller.php';

use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class ClubsController extends \Controller 
{
	protected $view_name = 'clubs';

	public function run_propose_club()
	{
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException();

		$member = get_identity()->member();

		$data = [
			'email' => $member['email'],
			'phone' => $member['telefoonnummer'],
		];

		$form = $this->createFormBuilder($data)
			->add('club_name', TextType::class, [
				'label' => __('Club name'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('description', TextareaType::class, [
				'label' => __('What is this club for?'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('motivation', TextareaType::class, [
				'label' => __('Why do you want to start this club?'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('members', TextareaType::class, [
				'label' => __('Members'),
				'constraints' => new Assert\NotBlank(),
				'help' => __('Do you know people who are interesed in joining? List their names here!'),
			])
			->add('communication_platform', TextType::class, [
				'label' => __('Preferred communication platform(s)'),
				'constraints' => new Assert\NotBlank(),
				'help' => __('The board will create a communication channel for you. Which platform(s) do you prefer for that?'),
			])
			->add('email', EmailType::class, [
				'label' => __('Email'),
				'help' => __('We need to know how to contact you for questions!'),
				'constraints' => [new Assert\NotBlank(), new Assert\Email()],
			])
			->add('phone', TelType::class, [
				'label' => __('Phone number'),
				'help' => __('We need to know how to contact you for questions!'),
				'constraints' => [
					new Assert\NotBlank(),
					new AssertPhoneNumber(['defaultRegion' => 'NL']),
				],
			])
			->add('submit', SubmitType::class, [
				'label' => __('Submit proposal'),
			])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			$mail = parse_email_object("club_proposal.txt", ['data' => $form->getData(), 'member' => $member]);
			$mail->send(get_config_value('email_bestuur'));
			$_SESSION['alert'] = __('Club proposal submitted! You should hear from the board soon!');
			return $this->view->redirect($this->generate_url('clubs'));
		}

		return $this->view->render('form.twig', ['form' => $form->createView()]);
	}

	public function run_index()
	{
		return $this->view->render('index.twig');
	}

	protected function run_impl()
	{
		$view = isset($_GET['view']) ? $_GET['view'] : 'index';
		return call_user_func([$this, 'run_' . $view]);
	}
}
