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

	protected function run_list_archive($list_id)
	{
		$lijst = $this->model->get_lijst($list_id);

		if (!$this->model->member_can_access_archive($lijst))
			return $this->get_content('common::auth');

		$model = get_model('DataModelMailinglijstArchief');

		$messages = $model->get_by_lijst($list_id);

		return $this->get_content('mailinglijsten::list_archive', null, compact('lijst', 'messages'));
	}

	protected function run_list_message($message_id)
	{
		$model = get_model('DataModelMailinglijstArchief');

		$message = $model->get_iter($message_id);

		$lijst = $this->model->get_iter($message->get('mailinglijst'));

		if (!$this->model->member_can_access_archive($lijst))
			return $this->get_content('common::auth');

		return $this->get_content('mailinglijsten::list_message', null, compact('message', 'lijst'));
	}

	protected function run_automessage_management($list_id, $message_category)
	{
		$list = $this->model->get_lijst($list_id);

		$errors = [];

		if (!$this->model->member_can_edit($list))
			throw new UnauthorizedException();

		switch ($message_category)
		{
			case 'subscription':
				$subject_field = 'on_subscription_subject';
				$message_field = 'on_subscription_message';
				break;

			case 'first_email':
				$subject_field = 'on_first_email_subject';
				$message_field = 'on_first_email_message';
				break;

			default:
				throw new Exception('Unknown message category');
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$list->set($subject_field, $_POST['subject']);
			$list->set($message_field, $_POST['message']);
			$list->update();
		}

		return $this->get_content('mailinglijsten::form_automessage', $list, compact('subject_field', 'message_field', 'errors'));
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
