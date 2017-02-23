<?php
require_once 'include/init.php';
require_once 'include/controllers/Controller.php';

class ControllerWachtwoordVergeten extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelMember');

		$this->view = View::byName('wachtwoordvergeten', $this);
	}
	
	protected function run_impl()
	{
		if (!empty($_POST['email']))
		{	
			$iter = $this->model->get_from_email(get_post('email'));
			
			if (!$iter)
				return $this->view->render_form(false, get_post('email'));

			$confkey = randstr(32);
			$values = array(
					'key' => $confkey, 
					'type' => 'wachtwoord',
					'value' => $iter['id']);
			
			get_db()->insert('confirm', $values);
			
			$language_code = strtolower(i18n_get_language());
			$variables = array(
				'naam' => $iter['voornaam'],
				'link' => 'https://www.svcover.nl/confirm.php?key=' . urlencode($confkey)
			);
			
			$email = parse_email_object("password_reset_{$language_code}.txt", $variables);

			$email->send($iter['email']);

			return $this->view->render_form(true, $iter['email']);
		} else {
			return $this->view->render_form(null, null);
		}			
	}
}

$controller = new ControllerWachtwoordVergeten();
$controller->run();
