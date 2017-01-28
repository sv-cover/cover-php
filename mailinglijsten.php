<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerMailinglijsten extends ControllerCRUD
{
	private $message_model;

	private $subscription_model;

	public function __construct()
	{
		$this->model = get_model('DataModelMailinglist');

		$this->message_model = get_model('DataModelMailinglistArchive');

		$this->subscription_model = get_model('DataModelMailinglistSubscription');

		$this->member_model = get_model('DataModelMember');

		$this->view = View::byName('mailinglijsten', $this);
	}

	protected function _index()
	{
		$iters = parent::_index();

		usort($iters, function($a, $b) {
			return strcasecmp($a['naam'], $b['naam']);
		});

		return $iters;
	}

	public function link_to_update_autoresponder(DataIterMailinglist $list, $message)
	{
		return $this->link(['id' => $list['id'], 'view' => 'update_autoresponder', 'autoresponder' => $message]);
	}

	protected function run_update_autoresponder(DataIterMailinglist $list)
	{
		if (!get_policy($this->model)->user_can_update($list))
			throw new Exception('You are not allowed to edit this ' . get_class($list) . '.');

		$success = false;

		$errors = array();

		$autoresponder = $_GET['autoresponder'];

		if (!in_array($autoresponder, ['on_subscription', 'on_first_email']))
			throw new InvalidArgumentException('Invalid value for autoresponder parameter');


		if ($this->_form_is_submitted('update', $list))
		{
			$data = [
				$autoresponder . '_subject' => $_POST[$autoresponder . '_subject'],
				$autoresponder . '_message' => $_POST[$autoresponder . '_message']
			];
		
			if ($this->_update($list, $data, $errors))
				$success = true;
		}

		return $this->view()->render_autoresponder_form($list, $autoresponder, $success, $errors);
	}

	protected function run_unsubscribe_confirm($subscription_id)
	{
		$subscription = $this->subscription_model->get_iter($subscription_id);

		$list = $subscription['mailinglist'];

		if ($subscription->is_active() && $this->_form_is_submitted('unsubscribe', $subscription))
			$subscription->cancel();

		return $this->view->render_unsubscribe_form($list, $subscription);
	}
	/*
	protected function run_update_subscriptions(DataIterMailinglist $lijst)
	{
		$return_url = isset($_POST['referer'])
			? $_POST['referer']
			: 'mailinglijsten.php?lijst_id=' . $lijst->get('id');

		$policy = get_policy($this->subscription_model);

		// Someone ordered someone execu.. unsubcribed? Bye bye.
		if (!empty($_POST['unsubscribe']) && $policy->user_can_edit($lijst))
		{
			foreach ($_POST['unsubscribe'] as $lid_id) {
				if (!ctype_digit($lid_id)) {
					$subscription = $this->subscription_model->get_iter($lid_id);
					$subscription->cancel();
				}
				else {
					$member = $this->member_model->get_iter($lid_id);
					$this->subscription_model->unsubscribe_member($lijst, $member);
				}
			}

			return $this->view->redirect($return_url);
		}

		if (!empty($_POST['subscribe']) && $this->model->member_can_edit($lijst))
		{
			// If the subscribe data isn't yet an array, split it on comma's.
			$subscribe_ids = is_array($_POST['subscribe'])
				? $_POST['subscribe']
				: preg_split('/[\s,]+/', $_POST['subscribe']);

			foreach ($subscribe_ids as $lid_id)
				if (!empty($lid_id))
					$this->model->aanmelden($lijst, $lid_id);

			return $this->redirect($return_url);
		}

		if (!empty($_POST['naam']) && !empty($_POST['email']) && $this->model->member_can_edit($lijst))
		{
			$this->model->aanmelden_gast($lijst, $_POST['naam'], $_POST['email']);

			return $this->redirect($return_url);
		}

		// If data to update the metadata of the list is passed on, well, make use of it.
		if (isset($_POST['naam'], $_POST['omschrijving']) && $this->model->member_can_edit($lijst))
		{
			$lijst->set('naam', $_POST['naam']);
			$lijst->set('omschrijving', $_POST['omschrijving']);
			$lijst->set('publiek', empty($_POST['publiek']) ? '0' : '1');
			$lijst->set('toegang', $_POST['toegang']);

			// Only the board can change the owner of a list
			if (member_in_commissie(COMMISSIE_BESTUUR))
				$lijst->set('commissie', $_POST['commissie']);

			$lijst->update();

			return $this->redirect($return_url);
		}

		$aangemeld = $this->model->is_aangemeld($lijst, logged_in('id'));

		$aanmeldingen = $this->model->get_aanmeldingen($lijst);

		$this->get_content('mailinglijsten::mailinglist', null, compact('lijst', 'aanmeldingen', 'aangemeld'));
	}
	*/

	private function _subscribe_member(DataIterMailinglist $list, $member_id, &$errors)
	{
		$subscribe_ids = is_array($member_id)
			? $member_id
			: preg_split('/[\s,]+/', $member_id);

		foreach ($subscribe_ids as $id) {
			if (ctype_digit($id)) {
				$member = $this->member_model->get_iter((int) $id);
				$this->subscription_model->subscribe_member($list, $member);
			}
		}

		return true;
	}

	private function _subscribe_guest(DataIterMailinglist $list, $data, &$errors)
	{
		if (empty($data['naam']))
			$errors[] = 'naam';

		if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
			$errors[] = 'email';

		if (count($errors) === 0) {
			$this->subscription_model->subscribe_guest($list, $data['naam'], $data['email']);
			return true;
		}

		return false;
	}

	protected function run_subscribe_member(DataIterMailinglist $list)
	{
		// Todo: instead of checking whether current user can update the list,
		// check whether they can create new subscription iterators according
		// to the policy?

		if (!get_policy($this->model)->user_can_update($list))
			throw new UnauthorizedException('You cannot modify this mailing list');

		$errors = array();

		if ($this->_form_is_submitted('subscribe_member', $list))
			if ($this->_subscribe_member($list, $_POST['member_id'], $errors))
				return $this->view->redirect($this->link_to_read($list));

		return $this->view->render_subscribe_member_form($list, $errors);
	}

	protected function run_subscribe_guest(DataIterMailinglist $list)
	{
		if (!get_policy($this->model)->user_can_update($list))
			throw new UnauthorizedException('You cannot modify this mailing list');

		$errors = array();

		if ($this->_form_is_submitted('subscribe_guest', $list))
			if ($this->_subscribe_guest($list, $_POST, $errors))
				return $this->view->redirect($this->link_to_read($list));

		return $this->view->render_subscribe_guest_form($list, $errors);
	}

	protected function run_unsubscribe(DataIterMailinglist $list)
	{
		if (!get_policy($this->model)->user_can_update($list))
			throw new UnauthorizedException('You cannot modify this mailing list');

		if ($this->_form_is_submitted('unsubscribe', $list))
		{
			foreach ($_POST['unsubscribe'] as $subscription_id)
			{
				$subscription = $this->subscription_model->get_iter($subscription_id);
				
				if ($subscription['mailinglijst_id'] != $list['id'])
					throw new NotFoundException('Subscription not in this list');

				$subscription->cancel();
			}
		}

		return $this->view->redirect($this->link_to_read($list));
	}

	protected function run_archive_index(DataIterMailinglist $list)
	{
		if (!$this->model->member_can_access_archive($list))
			throw new UnauthorizedException('You cannot access the archives of this mailing list');

		$model = get_model('DataModelMailinglistArchive');

		$messages = $model->get_for_list($list);

		return $this->view->render_archive_index($list, $messages);
	}

	protected function run_archive_read(DataIterMailinglist $list)
	{
		if (!$this->model->member_can_access_archive($list))
			throw new UnauthorizedException('You cannot access the archives of this mailing list');

		$model = get_model('DataModelMailinglistArchive');

		$message = $model->get_iter($_GET['message_id']);

		if ($message['mailinglijst'] != $list->get_id())
			throw new NotFoundException('No such message found in this mailing list');

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
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
	$controller = new ControllerMailinglijsten();
	$controller->run();
}
