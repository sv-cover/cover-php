<?php
	include('include/init.php');
	include('controllers/Controller.php');
	include('member.php');
	include('form.php');
	
	class ControllerLidWorden extends Controller {
		var $model = null;
		var $sizes = null;

		function ControllerLidWorden() {
			$this->model = get_model('DataModelMember');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => _('Lid worden')));
			run_view('lidworden::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}

		function _process_lidworden() {
			$data = check_values(array(
				'voornaam',
				'achternaam',
				'geslacht',
				'adres',
				'postcode',
				'woonplaats',
				'email',
				'studentnummer',
				'studierichting',
				'rekening',
				'year'				
				), $errors);
			// 20090907 update t.b.v. anti-spam
			if(strcasecmp($_POST['spam'], 'groen') != 0) {
				$errors[] = 'spam';
			}
			if($_POST['machtiging'] != 'yes') {
				$errors[] = 'machtiging';
			}
			
			if (count($errors) > 0) {
				if (in_array('year', $errors))
					$errors[] = 'geboortedatum';

				$this->get_content('lidworden', null, 
						array('errors' => $errors));
				return;
			}
			
			$data['tussenvoegsel'] = $_POST['tussenvoegsel'];
			$data['telefoonnummer'] = $_POST['telefoonnummer'];
			$data['geboortedatum'] = $_POST['day'] . '-' . $_POST['month'] . '-' . $_POST['year'];
			$data['machtiging'] = $_POST['machtiging'] == 'yes' ? 'Ja' : 'Nee';
			$data['fmflid'] = $_POST['fmflid'] == 'yes' ? 'Ja' : 'Nee';

			// Setup e-mail
			$mail = parse_email('lidworden.txt', $data);

			mail(get_config_value('email_bestuur'), 'Lidaanvraag', $mail, 'From: Cover <bestuur@svcover.nl>');
			header('Location: lidworden.php?verzonden');
		}
		
		function run_impl() {
			if (isset($_POST['submlidworden']))
				$this->_process_lidworden();
			else if (isset($_GET['verzonden']))
				$this->get_content('verzonden');
			else {
				$this->get_content('lidworden');
			}
		}
	}
	
	$controller = new ControllerLidWorden();
	$controller->run();
?>
