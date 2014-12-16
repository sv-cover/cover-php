<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/form.php';
	require_once 'include/controllers/Controller.php';
	
	class ControllerLidWorden extends Controller
	{
		var $model = null;
		var $sizes = null;

		function ControllerLidWorden() {
			$this->model = get_model('DataModelMember');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Lid worden')));
			run_view('lidworden::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}

		function _process_lidworden() {
			$data = check_values(array(
				'voornaam',
				'achternaam',
				'gender',
				'adres',
				'postcode',
				'woonplaats',
				'email',
				'studentnummer',
				'studierichting',
				'rekening',
				'year',
				'fase'
				), $errors);
			// 20090907 update t.b.v. anti-spam
			if(!in_array(strtolower($_POST['spam']), array('groen', 'green', 'coverrood', 'cover red'))) {
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
			$data['mailing'] = !empty($_POST['mailing']) ? 'Ja' : 'Nee';

			// Setup e-mail
			$mail = parse_email('lidworden.txt', $data);

			mail('administratie@svcover.nl', 'Lidaanvraag', $mail, 'From: Cover <board@svcover.nl>');

			mail('secretaris@svcover.nl', 'Lidaanvraag', "Er is een nieuwe lidaanvraag ingediend. De gegevens zijn te vinden op administratie@svcover.nl", 'From: Cover <board@svcover.nl>');
			
			header('Location: lidworden.php?verzonden=true');
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
