<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'controllers/Controller.php';

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
		$id = $this->model->create_lijst(
			$_POST['adres'], $_POST['naam'],
			$_POST['omschrijving'],
			!empty($_POST['publiek']),
			$_POST['toegang'],
			$_POST['commissie']);

		if ($id > 0)
			return header('Location: mailinglijsten.php?lijst_id=' . $id);
		
		echo 'Error';
	}

	protected function _process_remove_subscription()
	{
		$abonnement = $this->model->get_abonnement($_POST['abonnement_id']);

		$this->model->afmelden($_POST['abonnement_id']);

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

		// Someone ordered someone execu.. unsubcribed? Bye bye.
		if (!empty($_POST['unsubscribe']))
		{
			foreach ($_POST['unsubscribe'] as $abonnement_id)
				$this->model->afmelden($abonnement_id);

			header('Location: mailinglijsten.php?lijst_id=' . $lijst->get('id'));
			return;
		}

		if (!empty($_POST['subscribe']))
		{
			foreach (preg_split('/[\s,]+/', $_POST['subscribe']) as $lid_id)
				$this->model->aanmelden($lid_id, $lijst->get('id'));

			header(sprintf('Location: mailinglijsten.php?lijst_id=%d', $lijst->get('id')));
			return;
		}

		if (!empty($_POST['naam']) && !empty($_POST['email']))
		{
			$this->model->aanmelden_gast($_POST['naam'], $_POST['email'], $lijst->get('id'));

			header(sprintf('Location: mailinglijsten.php?lijst_id=%d', $lijst->get('id')));
			return;
		}

		// If data to update the metadata of the list is passed on, well, make use of it.
		if (isset($_POST['naam'], $_POST['omschrijving']))
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

		$aanmeldingen = $this->model->get_aanmeldingen($lijst->get('id'));

		$this->get_content('mailinglijsten::subscriptions', null, compact('lijst', 'aanmeldingen'));
	}

	protected function run_my_subscriptions_management($lid_id)
	{
		$member_model = get_model('DataModelMember');

		$member = $member_model->get_iter($lid_id);

		if (!$member) {
			header('Status: 404 Not found');
			$this->get_content('common::not_found');
			return;
		}
	
		if (!empty($_POST['action']))
		{
			switch ($_POST['action'])
			{
				case 'subscribe':
					$this->model->aanmelden($lid_id, $_POST['mailinglijst_id']);
					break;

				case 'unsubscribe':
					$this->model->afmelden($_POST['abonnement_id']);
					break;
			}

			if ($lid_id == logged_in('id'))
				header('Location: mailinglijsten.php');
			else
				header('Location: mailinglijsten.php?lid_id=' . $lid_id);

			return;
		}

		$subscriptions = $this->model->get_lijsten($lid_id,
			!member_in_commissie(COMMISSIE_EASY)); // public only? Only if not WebCie.

		$this->get_content('mailinglijsten::mailinglists', $subscriptions, compact('member'));
	}

	protected function run_autocomplete($query)
	{
		$model = get_model('DataModelMember');

		$suggestions = array();

		foreach ($model->search_first_last($query) as $member)
			$suggestions[] = array(
				'id' => $member->get('id'),
				'naam' => member_full_name($member));

		echo json_encode($suggestions);
	}

	public function run_impl()
	{
		// Unsubscribe link? Show the unsubscribe confirmation page
		if (!empty($_GET['abonnement_id']))
			return $this->run_unsubscribe_confirm($_GET['abonnement_id']);

		elseif (!empty($_GET['autocomplete']))
			return $this->run_autocomplete($_GET['naam']);

		// Manage the subscriptions to a list
		elseif (!empty($_GET['lijst_id']) && $this->model->member_can_edit($_GET['lijst_id']))
			return $this->run_subscriptions_management($_GET['lijst_id']);

		// Manage the subscriptions of a member other than yourself
		elseif (member_in_commissie(COMMISSIE_BESTUUR) && !empty($_GET['lid_id']))
			return $this->run_my_subscriptions_management($_GET['lid_id']);

		// No list but a post request -> create a new list
		elseif (member_in_commissie(COMMISSIE_EASY) && isset($_GET['lijst_id']))
			return $this->_process_new_list();

		// Manage your own subscriptions
		elseif ($me = logged_in())
			return $this->run_my_subscriptions_management($me['id']);

		// There isn't really anything you can do when not logged in, Sorry!
		else
			return $this->get_content('common::auth');
	}
}

$controller = new ControllerMailinglijsten();
$controller->run();
