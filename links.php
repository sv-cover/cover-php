<?php
	include('include/init.php');
	include('controllers/Controller.php');
	
	require_once('member.php');
	require_once('login.php');
	require_once('form.php');

	class ControllerLinks extends Controller {
		var $model = null;

		function ControllerLinks() {
			$this->model = get_model('DataModelLinks');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => _('Links')));
			run_view('links::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _page_prepare($commissie = -1) {
			if ($commissie != -1 && !member_in_commissie($commissie)) {
				$this->get_content('auth_bestuur');

				return false;
			}

			if (!logged_in()) {
				$this->get_content('auth');

				return false;
			}
			
			return true;
		}
		
		function _process_del() {
			if (!$this->_page_prepare(COMMISSIE_BESTUUR))
				return;

			foreach ($_POST as $field => $value) {
				if (!preg_match('/del_([0-9]+)/i', $field, $matches))
					continue;
				
				if ($value != 'yes')
					continue;
					
				$id = $matches[1];
				$iter = $this->model->get_iter($id);
				
				if (!$iter)
					continue;
				
				$this->model->delete($iter);				
			}
			
			header('Location: links.php');
		}
		
		function _process_categories() {
			if (!$this->_page_prepare(COMMISSIE_BESTUUR))
				return;

			if (get_post('categorie_lijst') == '')
				$new = array();
			else
				$new = explode(';', get_post('categorie_lijst'));

			$old = $this->model->get_categories();
			
			$ids = array();
			$iters = array();

			$i = 0;

			foreach ($old as $iter) {
				$ids[$i] = intval($iter->get_id());
				$i++;
			}
			
			$order = 1;

			foreach ($new as $item) {
				if (preg_match('/([0-9]+)=(.*)/i', $item, $matches)) {
					$id = $matches[1];
					$titel = $matches[2];
					
					if (($index = array_search(intval($id), $ids)) !== false) {
						/* Update the order of this one */
						$old[$index]->set('order', $order);
						$old[$index]->set('titel', $titel);
						
						$this->model->update_category($old[$index]);
						
						unset($ids[$index]);
					}
				} else {
					$iter = new DataIter($this->model, -1, 
							array(	'titel' => $item, 
								'order' => $order));
				
					$this->model->insert_category($iter);
				}
				
				$order++;
			}
			
			$errors = array();

			if (count($ids) > 0) {
				/* These need to be removed, but first check if 
				   there are no documents left in them */
				foreach ($ids as $i => $id) {
					$docs = $this->model->get_links($id);
					
					if (count($docs) != 0) {
						$errors[] = $old[$i]->get('titel');
					} else {
						$this->model->delete_category($old[$i]);
					}
				}
			}
			
			$params = array('expand' => true);

			if (count($errors) > 0)
				$params['delete_error'] = $errors;
			
			$this->get_content('links', null, $params);				
		}

		function _process_add() {
			if (!$this->_page_prepare())
				return;

			$data = check_values(array(
					array('name' => 'categorie', 'function' => 'check_value_toint'),
					'titel',
					'url'), $errors);
			
			if (count($errors) > 0) {
				$this->get_content('links', null, array('errors' => $errors, 'expand' => true));
				return;
			}
			
			/* Set to moderated if the user is in bestuur */			
			if (member_in_commissie(COMMISSIE_BESTUUR, false))
				$data['moderated'] = 1;

			$data['beschrijving'] = get_post('beschrijving');
			$member_data = logged_in();
			$iter = new DataIter($this->model, -1, $data);
			$iter->set('door', $member_data['id']);

			$id = $this->model->insert($iter, true);
			
			if (member_in_commissie(COMMISSIE_BESTUUR, false)) {
				header('Location: links.php');
				exit();
			}
			
			$data['id'] = $id;
			$data['member_naam'] = member_full_name();
			$data['categorie'] = $this->model->get_categorie_naam($data['categorie']);

			$_SESSION['alert'] = _('De nieuwe link is in de wachtrij geplaatst. Zodra het bestuur ernaar gekeken heeft zal de link op de website geplaatst worden');
			$body = parse_email('links_add.txt', $data);
			$subject = 'Nieuwe link ' . $data['titel'];
			
			mail(get_config_value('email_bestuur'), $subject, $body, "From: webcie@ai.rug.nl\r\n");

			header('Location: links.php');
			exit();
		}
		
		function _process_moderate() {
			$cancelled = array();
			
			$member_model = get_model('DataModelMember');

			foreach ($_POST as $field => $value) {
				if (!preg_match('/action_([0-9]+)/i', $field, $matches))
					continue;
				
				$id = $matches[1];
				$iter = $this->model->get_iter($id);
				
				if (!$iter)
					continue;

				if ($value == 'accept') {
					/* Accept link */
					$iter->set('moderated', 1);
					$iter->set('categorie', intval(get_post('categorie_' . $id)));
					$iter->set('beschrijving', get_post('beschrijving_' . $id));
					
					$this->model->update($iter);
				} elseif ($value == 'cancel') {
					/* Remove link and inform owner of the link */
					$this->model->delete($iter);

					$member = $member_model->get_iter($iter->get('door'));
					$email = $member->get('email');

					if ($email) {
						$data = $iter->data;
						$data['member_naam'] = member_full_name();
						$data['reden'] = get_post('comment_' . $id);

						$subject = 'Link ' . $iter->get('titel') . ' geweigerd';
						$body = parse_email('links_cancel.txt', $data);
					
						mail($email, $subject, $body, "From: webcie@ai.rug.nl\r\n");
					}

					$cancelled[] = member_full_name($member);
				}
			}
			
			$cancelled_un = array_unique($cancelled);
			$s = implode_human($cancelled_un);

			if (count($cancelled_un) == 1)
				if (count($cancelled) == 1) {
					$_SESSION['alert'] = sprintf(_('%s is op de hoogte gesteld van het weigeren van de link.'), $s);
				} else {
					$_SESSION['alert'] = sprintf(_('%s is op de hoogte gesteld van het weigeren van de links.'), $s);
				}
			elseif (count($cancelled_un) > 0)
				$_SESSION['alert'] = sprintf(_('%s zijn op de hoogte gesteld van het weigeren van de links.'), $s);
			
			header('Location: links.php');
			exit();
		}
		
		function _view_moderate($id) {
			$params = array('highlight' => $id);
			
			$iter = $this->model->get_moderates();
			$this->get_content('moderate', $iter, $params);
		}

		function run_impl() {
			if (isset($_POST['submlinks_categories']))
				$this->_process_categories();
			elseif (isset($_POST['submlinks_add']))
				$this->_process_add();
			elseif (isset($_POST['submlinks_del']))
				$this->_process_del();
			elseif (isset($_POST['submlinks_moderate']))
				$this->_process_moderate();
			elseif (isset($_GET['links_moderate']))
				$this->_view_moderate($_GET['links_moderate']);
			else
				$this->get_content('links');
		}
	}
	
	$controller = new ControllerLinks();
	$controller->run();
?>
