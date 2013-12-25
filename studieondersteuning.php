<?php
	include('include/init.php');
	include('controllers/Controller.php');
	include('controllers/ControllerEditable.php');

	require_once('form.php');

	class ControllerBoeken extends Controller {
		var $model = null;

		function ControllerBoeken() {
			$this->model = get_model('DataModelStudieondersteuning');
		}
		
		function get_content($view, $iter = null, $params = null, $show_editable = true) {
			$this->run_header(array('title' => __('Studieondersteuning')));
			
			if ($show_editable) {
				$editable = new ControllerEditable('Studieondersteuning');
				$editable->run();
			}

			run_view('studieondersteuning::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}

		function _process_add() {
			$errors = array();

			if (!isset($_GET['filename']) || !$_GET['filename'])
				$errors[] = 'bestand';
			else
				$filename = $_GET['filename'];
			
			if (!isset($_GET['vak']) || !is_numeric($_GET['vak']))
				$errors[] = 'vak';
			else
				$vak = intval($_GET['vak']);
			
			if (!isset($_GET['titel']) || !$_GET['titel'])
				$errors[] = 'titel';
			else
				$titel = $_GET['titel'];
			
			if (isset($_GET['titel']))
				$_POST['titel'] = $_GET['titel'];

			if (count($errors) > 0) {
				if ($filename)
					unlink('so_documenten/' . $filename);

				$this->get_content('studieondersteuning', null, array('errors' => $errors));
				return;
			}
			
			$member_data = logged_in();
			$data = array('vak' => $vak,
					'titel' => $titel,
					'bestand' => $filename,
					'beschrijving' => '-',
					'lid' => $member_data['id']);

			$iter = new DataIter($this->model, -1, $data);
			$id = $this->model->insert_document($iter);
			
			$vak = $this->model->get_vak($vak);
			$data = $iter->data;
			
			$data['id'] = $id;
			$data['vak'] = $vak->get('naam');
			$data['member_naam'] = member_full_name();
			$data['aantekeningen'] = ($vak->get('aantekeningen') ? 'Toegestaan' : 'Niet toegestaan');
			$data['tentamens'] = ($vak->get('tentamens') ? 'Toegestaan' : 'Niet toegestaan');
			$data['bijzonderheden'] = ($vak->get('bijzonderheden') ? $vak->get('bijzonderheden') : 'Geen');

			$subject = 'Nieuw studieondersteuning document';
			$body = parse_email('document_add.txt', $data);

			mail(get_config_value('email_bestuur'), $subject, $body, "From: webcie@ai.rug.nl\r\n");
			$this->get_content('document_added', null, null, false);
		}

		function _page_prepare() {
			if (!member_in_commissie(COMMISSIE_BESTUUR)) {
				$this->get_content('auth_bestuur', null, null, false);
				return false;
			}
			
			return true;
		}

		function _process_delete() {
			if (!$this->_page_prepare())
				return;
			
			foreach ($_POST as $field => $value) {
				if (!preg_match('/del__([0-9]+)/i', $field, $matches))
					continue;

				$id = $matches[1];
				$iter = $this->model->get_iter($id);
				
				if (!$iter)
					continue;
				
				unlink('so_documenten/' . $iter->get('bestand'));
				$this->model->delete($iter);
			}
			
			header('Location: ' . get_request());
			exit();
		}
		
		function _process_moderate() {
			if (!$this->_page_prepare())
				return;

			$cancelled = array();

			foreach ($_POST as $field => $value) {
				if (!preg_match('/action__([0-9]+)/i', $field, $matches))
					continue;
				
				$id = $matches[1];
				$iter = $this->model->get_iter($id);
				
				if (!$iter)
					continue;

				if ($value == 'accept') {
					/* Accept document */
					$iter->set('checked', 1);
					
					$this->model->update($iter);
				} elseif ($value == 'cancel') {
					/* Remove document and inform owner of the document */
					$this->model->delete($iter);
					
					unlink('so_documenten/' . $iter->get('bestand'));
					
					$data = $iter->data;
					$data['member_naam'] = member_full_name();
					$data['reden'] = get_post('comment_' . $id);

					$subject = 'Document ' . $iter->get('titel') . ' geweigerd';
					$body = parse_email('document_cancel.txt', $data);
					
					$member_model = get_model('DataModelMember');
					$member = $member_model->get_iter($iter->get('lid'));
					$email = $member->get('email');

					mail($email, $subject, $body, "From: webcie@ai.rug.nl\r\n");
					$cancelled[] = member_full_name($member);
				}
			}
			
			$cancelled_un = array_unique($cancelled);
			$s = implode(', ', $cancelled_un);

			if (count($cancelled_un) == 1)
				if (count($cancelled) == 1) {
					$_SESSION['alert'] = sprintf(__('%s is op de hoogte gesteld van het weigeren van het document.'), $s);
				} else {
					$_SESSION['alert'] = sprintf(__('%s is op de hoogte gesteld van het weigeren van de documenten.'), $s);
				}
			elseif (count($cancelled_un) > 0)
				$_SESSION['alert'] = sprintf(__('%s zijn op de hoogte gesteld van het weigeren van de documenten.'), $s);
			
			header('Location: studieondersteuning.php');
			exit();
		}
		
		function _view_moderate($id) {
			if (!$this->_page_prepare())
				return;

			$params = array('highlight' => $id);
			$iters = $this->model->get_moderates();
			$this->get_content('moderate', $iters, $params, false);
		}
		
		function _run_download($id) {
			$iter = $this->model->get_iter($id);
			
			if (!$iter->get('checked') && !member_in_commissie(COMMISSIE_BESTUUR)) {
				$this->get_content('auth_download', $iter, null, false);
				return;
			}
			
			$parts = pathinfo($iter->get('bestand'));
			
			if ($parts['extension'])
				$extension = '.' . $parts['extension'];
			else
				$extension = '';
			
			header('Content-Description: File Transfer');
			header('Content-Type: application/force-download');
			header('Content-Disposition: attachment; filename="' . $iter->get('titel') . $extension . '"');
			
			$fh = fopen(ROOT_DIR_PATH . 'so_documenten/' . $iter->get('bestand'), 'r');

			fpassthru($fh);
			fclose($fh);
			
			exit();
		}
		
		function _view_vak($id) {
			$vak = $this->model->get_vak($id);
			
			if (!$vak) {
				$this->get_content('vak_not_found', null, null, false);
				return;
			}

			$iters = $this->model->get_for_vak($id);
			$params = array('vak' => $vak);
			$this->get_content('vak', $iters, $params, false);
		}

		function run_impl() {
			if (!logged_in())
				$this->get_content('auth');
			elseif (isset($_GET['add']))
				$this->_process_add();
			elseif (isset($_POST['submso_del']))
				$this->_process_delete();
			elseif (isset($_POST['submso_moderate']))
				$this->_process_moderate();
			elseif (isset($_GET['so_moderate']))
				$this->_view_moderate($_GET['so_moderate']);
			elseif (isset($_GET['download']))
				$this->_run_download($_GET['download']);
			elseif (isset($_GET['vak']))
				$this->_view_vak($_GET['vak']);
			else
				$this->get_content('studieondersteuning');
		}
	}
	
	$controller = new ControllerBoeken();
	$controller->run();
?>
