<?php
	ini_set('display_errors', true);
	error_reporting(E_ALL ^ E_NOTICE);

	include('include/init.php');
	include('controllers/Controller.php');
	include_once('data/DataModel.php');

	class ControllerWachtwoordVergeten extends Controller {
		function ControllerWachtwoordVergeten() {
		}
		
		function get_content($view = '', $params = null) {
			$this->run_header(Array('title' => _('Wachtwoord vergeten')));
			run_view('wachtwoordvergeten' . ($view ? ('::' . $view) : ''), null, null, $params);
			$this->run_footer();
		}
		
		function run_impl() {
			if (isset($_POST['submsend'])) {
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
				
				$model = new DataModel(get_db(), 'confirm');
				$model->insert(new DataIter($model, -1, $values));
				
				$subject = _('Aanvraag nieuw wachtwoord');
				$body = "Iemand heeft een nieuw wachtwoord aangevraagd voor het account van dit e-mailadres op de Cover website. Om dit te bevestigen open je het volgende adres in je browser:\n\nhttp://www.svcover.nl/confirm.php?key=$confkey\n\nWeet je hier niks vanaf dat kan je dit mailtje negeren.\n\nMet vriendelijke groeten,\n\nDe WebCie";
				
				mail(get_post('email'), $subject, $body, "From: webcie@ai.rug.nl\r\n");
				$this->get_content('success', array('email' => get_post('email')));
			} else {
				$this->get_content();
			}			
		}
	}
	
	$controller = new ControllerWachtwoordVergeten();
	$controller->run();
?>
