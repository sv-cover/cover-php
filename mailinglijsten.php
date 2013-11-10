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

	protected function run_unsubscribe_confirm($abonnement_id)
	{
		$abonnement = $this->model->get_abonnement($abonnement_id);
		$uitgeschreven = false;

		if ($abonnement && !empty($_POST['unsubscribe'])) {
			$this->model->afmelden($abonnement->get('abonnement_id'));
			$uitgeschreven = true;
		}

		$this->get_content('uitschrijven', null, compact('abonnement', 'uitgeschreven'));
	}

	protected function run_subscriptions_management($lijst_id)
	{
		$lijst = $this->model->get_lijst($lijst_id);

		$aanmeldingen = $this->model->get_aanmeldingen($lijst->get('id'));

		$this->get_content('lijst', null, compact('lijst', 'aanmeldingen'));
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

		$subscriptions = $this->model->get_lijsten($me['id']);

		$this->get_content('mailinglijsten', $subscriptions);
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
