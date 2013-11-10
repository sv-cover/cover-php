<?php

require_once 'include/init.php';
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
		$this->model->create_lijst(
			$_POST['local_part'], $_POST['naam'],
			$_POST['omschrijving'], !empty($_POST['publiek']));

		header('Location: mailinglijsten.php');
	}

	protected function _process_add_subscription()
	{
		$this->model->aanmelden($_POST['lid_id'], $_POST['lijst_id']);

		header(sprintf('Location: mailinglijsten.php?lijst_id=%d', $_POST['lijst_id']));
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

		$aanmeldingen = $this->model->get_aanmeldingen($lijst->get('id'));

		$this->get_content('subscriptions', null, compact('lijst', 'aanmeldingen'));
	}

	protected function run_my_subscriptions_management()
	{
		$me = logged_in();
	
		if (!empty($_POST['action']))
		{
			switch ($_POST['action'])
			{
				case 'subscribe':
					$this->model->aanmelden($me['id'], $_POST['mailinglijst_id']);
					break;

				case 'unsubscribe':
					$this->model->afmelden($_POST['abonnement_id']);
					break;
			}
		}

		$subscriptions = $this->model->get_lijsten($me['id'], false);

		// var_dump($subscriptions);

		$this->get_content('mailinglists', $subscriptions);
	}

	public function run_impl()
	{
		if (!empty($_GET['abonnement_id']))
			$this->run_unsubscribe_confirm($_GET['abonnement_id']);
		elseif (logged_in() && !empty($_GET['lijst_id']))
			$this->run_subscriptions_management($_GET['lijst_id']);
		elseif (logged_in())
			$this->run_my_subscriptions_management();
		else
			$this->get_content('auth');
	}
}

$controller = new ControllerMailinglijsten();
$controller->run();
