<?php
	require_once 'include/init.php';
	require_once 'include/controllers/Controller.php';
	require_once 'include/data/DataModel.php';

	class ControllerConfirm extends Controller {
		var $model = null;

		function ControllerConfirm() {
			$this->model = new DataModel(get_db(), 'confirm', 'key');
		}

		function get_content($view, array $params = null) {
			$this->run_header(array('title' => 'Confirm'));
			run_view('confirm::' . $view, null, null, $params);
			$this->run_footer();
		}
		
		function confirm_wachtwoord(DataIter $iter) {
			$id = intval($iter->get('value'));
			
			$model = get_model('DataModelMember');
			$member = $model->get_iter($id);
			
			if (!$member) {
				$this->get_content('invalid_confirm');
				
				/* Delete this confirmation */
				$this->model->delete($iter);
				return;
			}
			
			$newpass = create_pronouncable_password();
			$model->set_password($member, $newpass);

			$subject = __('Nieuw wachtwoord');
			$body =  sprintf(__("Het wachtwoord van het account op dit E-Mail adres van de Cover site is gewijzigd. Je kunt nu inloggen met de volgende gegevens:\n\nE-Mail:\t\t%s\nWachtwoord:\t%s\n\nMet vriendelijke groeten,\n\nDe WebCie"), $member->get('email'), $newpass);
			
			mail($member->get('email'), $subject, $body, "From: webcie@ai.rug.nl\r\n");
			$this->get_content('wachtwoord_success', array('email' => $member->get('email')));
			$this->model->delete($iter);
		}

		function confirm_email(DataIter $iter) {
			$payload = json_decode($iter->get('value'), true);

			$model = get_model('DataModelMember');
			$member = $model->get_iter($payload['lidid']);
			$member['email'] = $payload['email'];
			$model->update($member);

			$this->model->delete($iter);

			$this->get_content('email_success', ['member' => $member]);
		}
		
		function run_impl() {
			if (!isset($_GET['key']) || strlen($_GET['key']) != 32) {
				$this->get_content('invalid_key');
				return;
			}

			$iter = $this->model->get_iter($_GET['key']);
			
			if (!$iter) {
				$this->get_content('invalid_key');
				return;
			}
			
			$func = 'confirm_' . $iter->get('type');
			
			if (!method_exists($this, $func)) {
				$this->get_content('invalid_key');
				return;
			}
			
			call_user_func(array($this, $func), $iter);
		}
	}
	
	$controller = new ControllerConfirm();
	$controller->run();
