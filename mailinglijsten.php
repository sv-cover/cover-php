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
		$this->run_header(array('title' => _('Mailinglijsten')));
		run_view('mailinglijsten::' . $view, $this->model, $iter, $params);
		$this->run_footer();
	}

	protected function _process_new_list()
	{
		$id = $this->model->create_lijst(
			$_POST['adres'], $_POST['naam'],
			$_POST['omschrijving'], !empty($_POST['publiek']));

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

		$this->get_content('unsubscribe', null, compact('abonnement', 'uitgeschreven'));
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

		// If data to update the metadata of the list is passed on, well, make use of it.
		if (isset($_POST['naam'], $_POST['omschrijving']))
		{
			$lijst->set('naam', $_POST['naam']);
			$lijst->set('omschrijving', $_POST['omschrijving']);
			$lijst->set('publiek', empty($_POST['publiek']) ? '0' : '1');
			$lijst->update();

			header('Location: mailinglijsten.php?lijst_id=' . $lijst->get('id'));
			return;
		}

		$aanmeldingen = $this->model->get_aanmeldingen($lijst->get('id'));

		$this->get_content('subscriptions', null, compact('lijst', 'aanmeldingen'));
	}

	protected function run_my_subscriptions_management($lid_id)
	{
		$me = logged_in();
	
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
		}

		$subscriptions = $this->model->get_lijsten($lid_id,
			!member_in_commissie(COMMISSIE_EASY)); // public only? Only if not WebCie.

		$this->get_content('mailinglists', $subscriptions);
	}

	public function run_impl()
	{
		// Unsubscribe link? Show the unsubscribe confirmation page
		if (!empty($_GET['abonnement_id']))
			return $this->run_unsubscribe_confirm($_GET['abonnement_id']);

		// Manage the subscriptions to a list
		elseif (member_in_commissie(COMMISSIE_BESTUUR) && !empty($_GET['lijst_id']))
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
			return $this->get_content('auth');
	}
}

$controller = new ControllerMailinglijsten();
$controller->run();
