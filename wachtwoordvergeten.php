<?php
	require_once 'include/init.php';
	require_once 'include/controllers/Controller.php';
	include_once 'include/data/DataModel.php';

	class ControllerWachtwoordVergeten extends Controller {
		function ControllerWachtwoordVergeten() {
		}
		
		function get_content($view = '', $params = null) {
			$this->run_header(Array('title' => __('Wachtwoord vergeten')));
			run_view('wachtwoordvergeten' . ($view ? ('::' . $view) : ''), null, null, $params);
			$this->run_footer();
		}
		
		function run_impl() {
			if (isset($_POST['submsend']) && !empty($_POST['email'])) {
				$model = get_model('DataModelMember');
				$iter = $model->get_from_email(get_post('email'));
				
				if (!$iter) {
					$this->get_content('noaccount', array('email' => get_post('email')));
					return;
				}

				$confkey = randstr(32);
				$values = array(
						'key' => $confkey, 
						'type' => 'wachtwoord',
						'value' => $iter->get('id'));
				
				$model = new DataModel(get_db(), 'confirm', null);
				$model->insert(new DataIter($model, -1, $values));
				
				$language_code = strtolower(i18n_get_language());
				$variables = array(
					'naam' => $iter['voornaam'],
					'link' => 'https://www.svcover.nl/confirm.php?key=' . urlencode($confkey)
				);
				
				parse_email_object("password_reset_{$language_code}.txt", $variables)->send($iter['email']);

				$this->get_content('success', array('email' => $iter['email']));
			} else {
				$this->get_content();
			}			
		}
	}
	
	$controller = new ControllerWachtwoordVergeten();
	$controller->run();
