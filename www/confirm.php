<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';
require_once 'include/data/DataModel.php';

class ControllerConfirm extends Controller
{
	public function __construct()
	{
		$this->model = new DataModel(get_db(), 'confirm', 'key');

		$this->view = View::byName('confirm', $this);
	}

	protected function confirm_wachtwoord(DataIter $iter)
	{
		$id = intval($iter->get('value'));
		
		$model = get_model('DataModelMember');
		
		try {
			$member = $model->get_iter($id);
		} catch (DataIterNotFoundException $e) {
			throw new RuntimeException("Could not find the member associated with this confirmation");
		}
		
		$newpass = create_pronouncable_password();
		$model->set_password($member, $newpass);

		$subject = __('Nieuw wachtwoord');
		$body =  sprintf(__("Het wachtwoord van het account op dit E-Mail adres van de Cover site is gewijzigd. Je kunt nu inloggen met de volgende gegevens:\n\nE-Mail:\t\t%s\nWachtwoord:\t%s\n\nMet vriendelijke groeten,\n\nDe WebCie"), $member->get('email'), $newpass);
		
		mail($member->get('email'), $subject, $body, "From: webcie@ai.rug.nl\r\n");
		
		$this->model->delete($iter);

		return $this->view->render_password_reset($member);
	}

	protected function confirm_email(DataIter $iter)
	{
		$payload = json_decode($iter->get('value'), true);

		$model = get_model('DataModelMember');
		$member = $model->get_iter($payload['lidid']);
		$member['email'] = $payload['email'];
		$model->update($member);

		$this->model->delete($iter);

		return $this->view->render_email_confirmed($member);
	}
	
	protected function run_impl()
	{
		if (!isset($_GET['key']) || strlen($_GET['key']) != 32)
			return $this->view->render_invalid_key();

		try {
			$iter = $this->model->get_iter($_GET['key']);
		
			$func = 'confirm_' . $iter->get('type');
			
			if (!method_exists($this, $func))
				return $this->view->render_invalid_key();
				
			return call_user_func(array($this, $func), $iter);
		} catch (DataIterNotFoundException $e) {
			return $this->view->render_invalid_key();
		}
	}
}

$controller = new ControllerConfirm();
$controller->run();
