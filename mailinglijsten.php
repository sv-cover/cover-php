<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/controllers/Controller.php';

class ControllerMailinglijsten extends Controller
{
	public $model;

	public function __construct()
	{
		$this->model = get_model('DataModelMailinglijst');
	}

	private function update_subscriptions(array $subscriptions)
	{
		$me = logged_in();

		$this->model->update_abonnementen($me['id'], $subscriptions);
	}

	public function get_content($view, $iter = null, $params = null)
	{
		$this->run_header(array(
			'title' => isset($params['lijst'])
				? ucfirst($params['lijst']->get('naam')) . ' ' . __('Mailinglijst')
				: __('Mailinglijsten')));

		run_view($view, $this->model, $iter, $params);
		$this->run_footer();
	}

	protected function _process_new_list()
	{
		if (!member_in_commissie(COMMISSIE_EASY))
			return $this->get_content('common::auth');

		$id = $this->model->create_lijst(
			$_POST['adres'], $_POST['naam'],
			$_POST['omschrijving'],
			!empty($_POST['publiek']),
			$_POST['type'],
			$_POST['toegang'],
			$_POST['commissie']);

		if ($id > 0)
			return header('Location: mailinglijsten.php?lijst_id=' . $id);
		
		echo 'Error';
	}

	protected function _process_remove_subscription()
	{
		$abonnement = $this->model->get_abonnement($_POST['abonnement_id']);

		$this->model->afmelden_via_abonnement_id($_POST['abonnement_id']);

		header(sprintf('Location: mailinglijsten.php?lijst_id=%d', $abonnement->get('lijst_id')));
	}

	protected function run_unsubscribe_confirm($abonnement_id)
	{
		$abonnement = $this->model->get_abonnement($abonnement_id);
		$uitgeschreven = false;

		if ($abonnement && !empty($_POST['unsubscribe'])) {
			$this->model->afmelden($abonnement->get('abonnement_id'));
			$uitgeschreven = true;
		}

		$this->get_content('mailinglijsten::unsubscribe', null, compact('abonnement', 'uitgeschreven'));
	}

	protected function run_subscriptions_management($lijst_id)
	{
		$lijst = $this->model->get_lijst($lijst_id);

		if (isset($_POST['action']))
		{
			switch ($_POST['action'])
			{
				case 'subscribe':
					if ($this->model->member_can_subscribe($lijst))
						$this->model->aanmelden($lijst, logged_in('id'));
					break;

				case 'unsubscribe':
					if ($this->model->member_can_unsubscribe($lijst))
						$this->model->afmelden($lijst, logged_in('id'));
					break;
			}
			
			$this->redirect(!empty($_POST['referer'])
				? $_POST['referer']
				: 'mailinglijsten.php?lijst_id=' . $lijst->get('id'));
			return;
		}

		// Someone ordered someone execu.. unsubcribed? Bye bye.
		if (!empty($_POST['unsubscribe']) && $this->model->member_can_edit($_GET['lijst_id']))
		{
			foreach ($_POST['unsubscribe'] as $lid_id)
				if (!ctype_digit($lid_id))
					$this->model->afmelden_via_abonnement_id($lid_id);
				else
					$this->model->afmelden($lijst, $lid_id);

			header('Location: mailinglijsten.php?lijst_id=' . $lijst->get('id'));
			return;
		}

		if (!empty($_POST['subscribe']) && $this->model->member_can_edit($_GET['lijst_id']))
		{
			foreach (preg_split('/[\s,]+/', $_POST['subscribe']) as $lid_id)
				if (!empty($lid_id))
					$this->model->aanmelden($lijst, $lid_id);

			header(sprintf('Location: mailinglijsten.php?lijst_id=%d', $lijst->get('id')));
			return;
		}

		if (!empty($_POST['naam']) && !empty($_POST['email']) && $this->model->member_can_edit($_GET['lijst_id']))
		{
			$this->model->aanmelden_gast($lijst, $_POST['naam'], $_POST['email']);

			header(sprintf('Location: mailinglijsten.php?lijst_id=%d', $lijst->get('id')));
			return;
		}

		// If data to update the metadata of the list is passed on, well, make use of it.
		if (isset($_POST['naam'], $_POST['omschrijving']) && $this->model->member_can_edit($_GET['lijst_id']))
		{
			$lijst->set('naam', $_POST['naam']);
			$lijst->set('omschrijving', $_POST['omschrijving']);
			$lijst->set('publiek', empty($_POST['publiek']) ? '0' : '1');
			$lijst->set('toegang', $_POST['toegang']);

			// Only the board can change the owner of a list
			if (member_in_commissie(COMMISSIE_BESTUUR))
				$lijst->set('commissie', $_POST['commissie']);

			$lijst->update();

			header('Location: mailinglijsten.php?lijst_id=' . $lijst->get('id'));
			return;
		}

		$aangemeld = $this->model->is_aangemeld($lijst, logged_in('id'));

		$aanmeldingen = $this->model->get_aanmeldingen($lijst);

		$this->get_content('mailinglijsten::mailinglist', null, compact('lijst', 'aanmeldingen', 'aangemeld'));
	}

	protected function run_my_subscriptions_management()
	{
		$member_model = get_model('DataModelMember');

		$member = $member_model->get_iter(logged_in('id'));

		if (!$member) {
			header('Status: 404 Not found');
			$this->get_content('common::not_found');
			return;
		}
	
		if (!empty($_POST['action']))
		{
			$lijst = $this->model->get_lijst($_POST['mailinglijst_id']);

			switch ($_POST['action'])
			{
				case 'subscribe':
					if ($this->model->member_can_subscribe($lijst))
						$this->model->aanmelden($lijst, logged_in('id'));
					break;

				case 'unsubscribe':
					if ($this->model->member_can_unsubscribe($lijst))
						$this->model->afmelden($lijst, logged_in('id'));
					break;
			}

			header('Location: mailinglijsten.php');
			return;
		}

		$subscriptions = $this->model->get_lijsten(logged_in('id'),
			!member_in_commissie(COMMISSIE_BESTUUR)); // public only? Only if not WebCie.

		$this->get_content('mailinglijsten::mailinglists', $subscriptions, compact('member'));
	}

	protected function show_list_archive($list_id)
	{
		$lijst = $this->model->get_lijst($list_id);

		if (!$this->model->member_can_access_archive($lijst))
			return $this->get_content('common::auth');

		$model = get_model('DataModelMailinglijstArchief');

		$messages = $model->get_by_lijst($list_id);

		return $this->get_content('mailinglijsten::list_archive', null, compact('lijst', 'messages'));
	}

	protected function show_list_message($message_id)
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

		if (!$this->model->member_can_edit($list_id))
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

	public function run_embedded($lijst_id)
	{
		$lijst = $this->model->get_lijst($lijst_id);

		if (!$lijst)
			return;

		$aangemeld = logged_in() && $this->model->is_aangemeld($lijst, logged_in('id'));

		run_view('mailinglijsten::embedded', $this->model, $lijst, compact('aangemeld'));
	}

	public function run_impl()
	{
		// Unsubscribe link? Show the unsubscribe confirmation page
		if (!empty($_GET['abonnement_id']))
			return $this->run_unsubscribe_confirm($_GET['abonnement_id']);

		// Manage the subscriptions to a list
		elseif (!empty($_GET['lijst_id']))
			if (isset($_GET['edit_message']))
				return $this->run_automessage_management($_GET['lijst_id'], $_GET['edit_message']);
			else
				return $this->run_subscriptions_management($_GET['lijst_id']);

		// Read archive
		elseif (!empty($_GET['archive_list_id']))
			return $this->show_list_archive($_GET['archive_list_id']);

		// Read archived message
		elseif (!empty($_GET['archive_message_id']))
			return $this->show_list_message($_GET['archive_message_id']);

		// No list but a post request -> create a new list
		elseif ($_SERVER['REQUEST_METHOD'] == 'POST')
			return $this->_process_new_list();

		// Manage your own subscriptions
		elseif ($me = logged_in())
			return $this->run_my_subscriptions_management($me['id']);

		// There isn't really anything you can do when not logged in, Sorry!
		else
			return $this->get_content('common::auth');
	}
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
	$controller = new ControllerMailinglijsten();
	$controller->run();
}
