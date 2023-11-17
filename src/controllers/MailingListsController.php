<?php
namespace App\Controller;

require_once 'src/framework/member.php';
require_once 'src/framework/controllers/ControllerCRUD.php';

use App\Form\MailinglistType;
use App\Form\Type\MemberIdType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;


class MailingListsController extends \ControllerCRUD
{
	private $message_model;

	private $subscription_model;

	protected $view_name = 'mailinglists';

	protected $form_type = MailinglistType::class;

	protected $member_model;

	public function __construct(Request $request = null, RouterInterface $router = null)
	{
		$this->model = get_model('DataModelMailinglist');

		$this->message_model = get_model('DataModelMailinglistArchive');

		$this->subscription_model = get_model('DataModelMailinglistSubscription');

		$this->member_model = get_model('DataModelMember');

		// Ideally, we would pass this to the parent, but since this constructor is called in markup.php, we need to handle the edge case where $request and $router may not be set.
		$this->request = $request;
		$this->router = $router;

		$this->view = \View::byName($this->view_name, $this);
	}

	public function path(string $view, \DataIter $iter = null)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
			$parameters['id'] = $iter->get_id();

		return $this->generate_url('mailing_lists', $parameters);
	}

	public function new_iter()
	{
		/* Set intial values in form (note the difference between an initial value and empty_data) */
		return $this->model->new_iter(['has_members' => true, 'tag' => 'Cover']);
	}

	protected function _index()
	{
		$iters = parent::_index();

		usort($iters, function($a, $b) {
			return strcasecmp($a['naam'], $b['naam']);
		});

		return $iters;
	}

	protected function run_update_autoresponder(\DataIterMailinglist $iter)
	{
		if (!get_policy($this->model)->user_can_update($iter))
			throw new \Exception('You are not allowed to edit this ' . get_class($iter) . '.');

		$autoresponder = $_GET['autoresponder'];

		if (!in_array($autoresponder, ['on_subscription', 'on_first_email']))
			throw new \InvalidArgumentException('Invalid value for autoresponder parameter');

		$builder = $this->createFormBuilder($iter)
			->add($autoresponder . '_subject', TextType::class, [
				'label' => __('Subject'),
				'required' => false,
			])
			->add($autoresponder . '_message', TextareaType::class, [
				'label' => __('Message'),
				'required' => false,
			])
			->add('submit', SubmitType::class);

		$builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($autoresponder) {
			$submittedData = $event->getData();
			if (empty($submittedData[$autoresponder . '_subject']) xor empty($submittedData[$autoresponder . '_message'])) {
				// This will be a global error message on the form, not on any specific field
				throw new TransformationFailedException(
					'message and subject must be set',
					0, // code
					null, // previous
					__('Both message and subject must be set for the automatic email to work. Clear both to stop sending automatic emails.'), // user message
				);
			}
		});

		$form = $builder->getForm();
		$form->handleRequest($this->get_request());

		$success = false;

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_update($iter, $form))
				$success = true;

		return $this->view()->render_autoresponder_form($iter, $form, $autoresponder, $success);
	}

	protected function run_unsubscribe_confirm($subscription_id)
	{
		/**
		 * Endpoint to allow people to unsubscribe through an unsubscribe link.
		 * TODO: better naming to differentiate between admin and user actions
		 */
		try {
			$subscription = $this->subscription_model->get_iter($subscription_id);
			$list = $subscription['mailinglist'];
		} catch (\DataIterNotFoundException $e) {
			if (preg_match('/^(\d+)\-(\d+)$/', $subscription_id, $match))
				$subscription = $this->subscription_model->new_iter([
					'opgezegd_op' => '1993-09-20 00:00:00',
					'lid_id' => (int) $match[2],
					'mailinglijst_id' => (int) $match[1],
				]);
			else
				throw $e;
		}

		$list = $subscription['mailinglist'];

		$form = $this->createFormBuilder()
			->add('submit', SubmitType::class, ['label' => 'Unsubscribe'])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($subscription->is_active() && $form->isSubmitted() && $form->isValid())
			$subscription->cancel();

		return $this->view->render_unsubscribe_form($list, $subscription, $form);
	}

	protected function run_subscribe(\DataIterMailinglist $list)
	{
		/**
		 * Endpoint to allow members to (un)subscribe from their profile page.
		 * TODO: better naming to differentiate between admin and user actions
		 */
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException('You need to log in to manage your mailinglist subscriptions');

		$member = get_identity()->member();

		$form = $this->createFormBuilder(null, ['csrf_token_id' => 'mailinglist_subscription_' . $list['id']])
			->add('subscribe', CheckboxType::class, [
				'label' => __('Subscribe'),
				'required' => false,
			])
			->add('do_subscribe', SubmitType::class, ['label' => __('Subscribe')])
			->add('do_unsubscribe', SubmitType::class, ['label' => __('Unsubscribe')])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			// Subscribe changes only if JS works, otherwise do_subscribe and do_unsubscribe should override whatever value is set.
			// So only subscribe if either do_subscribe is clicked or subscribe is checked without do_unsubscribe being clicked.
			$subscribe = $form->get('do_subscribe')->isClicked()
				|| ($form->get('subscribe')->getData() && !$form->get('do_unsubscribe')->isClicked());
			if ($subscribe) {
				if (get_policy($this->model)->user_can_subscribe($list))
					$this->subscription_model->subscribe_member($list, $member);
			} else {
				if (get_policy($this->model)->user_can_unsubscribe($list))
					$this->subscription_model->unsubscribe_member($list, $member);	
			}
		}

		$referrer = $this->get_parameter(
			'referrer',
			$this->generate_url('profile', ['view' => 'mailing_lists', 'lid' => $member['id']])
		);
		return $this->view->redirect($referrer);
	}

	private function _subscribe_member(\DataIterMailinglist $list, $data)
	{
		$member = $this->member_model->get_iter($data['member_id']);
		$this->subscription_model->subscribe_member($list, $member);
		return true;
	}

	private function _subscribe_guest(\DataIterMailinglist $list, $data)
	{
		return $this->subscription_model->subscribe_guest($list, $data['name'], $data['email']);
	}

	protected function run_subscribe_member(\DataIterMailinglist $list)
	{
		/**
		 * Endpoint to allow list owners to manually subscribe members.
		 *
		 * TODO: better naming to differentiate between admin and user actions
		 * TODO: instead of checking whether current user can update the list,
		 * check whether they can create new subscription iterators according
		 * to the policy?
		 */

		if (!get_policy($this->model)->user_can_update($list))
			throw new \UnauthorizedException('You cannot modify this mailing list');

		$form = $this->createFormBuilder()
			->add('member_id', MemberIdType::class, [
				'label' => __('Member'),
			])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_subscribe_member($list, $form->getData()))
				return $this->view->redirect($this->generate_url('mailing_lists', ['view' => 'read', $this->_var_id => $list->get_id()]));

		return $this->view->render_subscribe_member_form($list, $form);
	}

	protected function run_subscribe_guest(\DataIterMailinglist $list)
	{
		/**
		 * Endpoint to allow list owners to manually subscribe non-members.
		 *
		 * TODO: better naming to differentiate between admin and user actions
		 */
		if (!get_policy($this->model)->user_can_update($list))
			throw new \UnauthorizedException('You cannot modify this mailing list');

		$form = $this->createFormBuilder()
			->add('name', TextType::class, [
				'label' => __('Name'),
				'constraints' => [new NotBlank()],
			])
			->add('email', EmailType::class, [
				'label' => __('E-mail address'),
				'constraints' => [new NotBlank(), new Email()],
			])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_subscribe_guest($list, $form->getData()))
				return $this->view->redirect($this->generate_url('mailing_lists', ['view' => 'read', $this->_var_id => $list->get_id()]));

		return $this->view->render_subscribe_guest_form($list, $form);
	}

	protected function run_unsubscribe(\DataIterMailinglist $list)
	{
		/**
		 * Endpoint to allow list owners to manually unsubscribe members and non-members.
		 */
		if (!get_policy($this->model)->user_can_update($list))
			throw new \UnauthorizedException('You cannot modify this mailing list');

		$form = $this->createFormBuilder(null, ['csrf_token_id' => 'unsubscribe_' . $list['id']])
			->add('unsubscribe', ChoiceType::class, [
				'expanded' => true,
				'multiple' => true,
				'choices' => $list->get_subscriptions(),
				'choice_label' => function ($entity) {
					return $entity['name'] ?? $entity['lid_id'] ?? 'Unknown';
				},
				'choice_value' => function ($entity) {
					return $entity['id'] ?? '';
				},
			])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
			foreach ($form->get('unsubscribe')->getData() as $subscription)
				$subscription->cancel();

		return $this->view->redirect($this->generate_url('mailing_lists', ['view' => 'read', $this->_var_id => $list->get_id()]));
	}

	protected function run_archive_index(\DataIterMailinglist $list)
	{
		if (!$this->model->member_can_access_archive($list))
			throw new \UnauthorizedException('You cannot access the archives of this mailing list');

		$model = get_model('DataModelMailinglistArchive');

		$messages = $model->get_for_list($list);

		return $this->view->render_archive_index($list, $messages);
	}

	protected function run_archive_read(\DataIterMailinglist $list)
	{
		if (!$this->model->member_can_access_archive($list))
			throw new \UnauthorizedException('You cannot access the archives of this mailing list');

		$model = get_model('DataModelMailinglistArchive');

		$message = $model->get_iter($_GET['message_id']);

		if ($message['mailinglijst'] != $list->get_id())
			throw new \NotFoundException('No such message found in this mailing list');

		return $this->view->render_archive_read($list, $message);
	}

	protected function run_impl()
	{
		// Unsubscribe link? Show the unsubscribe confirmation page
		if (!empty($_GET['abonnement_id']))
			return $this->run_unsubscribe_confirm($_GET['abonnement_id']);
		else
			return parent::run_impl();
	}

	public function run_embedded($mailinglist_id)
	{
		$mailing_list = ctype_digit($mailinglist_id)
			? $this->model->get_iter($mailinglist_id)
			: $this->model->get_iter_by_address($mailinglist_id);

		$form = $this->createFormBuilder()
			->add('unsubscribe', SubmitType::class)
			->add('subscribe', SubmitType::class)
			->getForm();
		$form->handleRequest(get_request()); // use global get_request, as this controller is not properly initialised

		if ($form->isSubmitted() && $form->isValid())
		{
			// TODO: provide feedback on policy failures
			if ($form->getClickedButton() === $form->get('subscribe') && get_policy($this->model)->user_can_subscribe($mailing_list))
				$this->subscription_model->subscribe_member($mailing_list, get_identity()->member());

			if ($form->getClickedButton() === $form->get('unsubscribe') && get_policy($this->model)->user_can_unsubscribe($mailing_list))
				$this->subscription_model->unsubscribe_member($mailing_list, get_identity()->member());
		}

		return $this->view->render_embedded($mailing_list, $form);
	}
}
