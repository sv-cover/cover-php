<?php
	include('include/init.php');
	include('controllers/Controller.php');
	include('controllers/ControllerEditable.php');

	require_once('form.php');

	class ControllerBoeken extends Controller {
		var $model = null;

		function ControllerBoeken() {
			$this->model = get_model('DataModelBoeken');
		}
		
		function get_content($view, $iter = null, $params = null, $show_editable = true) {
			$this->run_header(array('title' => __('Boeken')));
			
			if ($show_editable) {
				$editable = new ControllerEditable('Boeken bestellen');
				$editable->run();
			}

			run_view('boeken::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		// function _page_prepare($id = null, $in_commissie = true) {
		// 	if (!logged_in()) {
		// 		$this->get_content('auth');
		// 		return false;
		// 	}
	
		// 	if ($in_commissie && !member_in_commissie(COMMISSIE_BOEKCIE)) {
		// 		$this->get_content('boekcie');
		// 		return false;
		// 	}
			
		// 	if ($id === null)
		// 		return true;
			
		// 	if (!($iter = $this->model->get_iter($id))) {
		// 		$this->get_content('not_found');
		// 		return false;
		// 	}
			
		// 	return $iter;
		// }
		
		// function _process_add() {
		// 	if (!$this->_page_prepare())
		// 		return;
			
		// 	$check = array('vak', 
		// 			'titel', 
		// 			'auteur', 
		// 			array('name' => 'prijs', 'function' => 'check_value_tofloat'),
		// 			array('name' => 'categorie', 'function' => 'check_value_toint'));
			
		// 	$data = check_values($check, $errors);
			
		// 	if (count($errors) > 0) {
		// 		$this->get_content('boeken', null, array('errors' => $errors));
		// 		return;
		// 	}
			
		// 	$data['status'] = 1;

		// 	$iter = new DataIter($this->model, -1, $data);
		// 	$this->model->insert($iter);
			
		// 	header('Location: ' . add_request(get_request(), 'added=yes'));
		// }
		
		// function _process_edit() {
		// 	if ($this->_page_prepare() === false)
		// 		return;
			
		// 	$all_errors = array();
		// 	$deletes = array();

		// 	foreach ($_POST as $field => $value) {
		// 		if (strncmp($field, "id_", 3) != 0)
		// 			continue;
				
		// 		$id = substr($field, 3);
		// 		$iter = $this->model->get_iter($id);
				
		// 		if (!$iter)
		// 			continue;
					
		// 		if (get_post('del_' . $id) == 'yes') {
		// 			if ($this->model->num_bestellingen($iter) > 0) {
		// 				/* This book still has orders,
		// 				 * inform the user
		// 				 */
		// 				$deletes[] = $iter;
		// 				continue;
		// 			} else {
		// 				/* Delete this book now */
		// 				$this->model->delete($iter);
		// 				continue;
		// 			}
		// 		}
				
		// 		if (get_post('suspend_' . $id) == 'yes' && $iter->get('status')) {
		// 			/* Mark the status as suspended */
		// 			$iter->set('status', 0);
		// 			$this->model->update($iter);
		// 		} elseif (get_post('suspend_' . $id) != 'yes' && !$iter->get('status')) {
		// 			$iter->set('status', 1);
		// 			$this->model->update($iter);
		// 		}

		// 		$check = array('vak_' . $id, 
		// 				'titel_' . $id, 
		// 				'auteur_' . $id, 
		// 				array('name' => 'prijs_' . $id, 'function' => 'check_value_tofloat'));
			
		// 		$data = check_values($check, $errors);

		// 		if (count($errors) > 0) {
		// 			$all_errors += $errors;
		// 			continue;
		// 		}
			
		// 		$iter->set('vak', $data['vak_' . $id]);
		// 		$iter->set('titel', $data['titel_' . $id]);
		// 		$iter->set('auteur', $data['auteur_' . $id]);
		// 		$iter->set('prijs', $data['prijs_' . $id]);
				
		// 		$this->model->update($iter);
		// 	}
			
		// 	/* Globale bestel status opslaan */
		// 	$configuratie = get_model('DataModelConfiguratie');
			
		// 	$iter = new DataIter($configuratie, 'boeken_bestellen', array());
		// 	$iter->set('value', get_post('vastzetten') == 'yes' ? '0' : '1');

		// 	$configuratie->update($iter);
			
		// 	if (count($deletes) > 0) {
		// 		$this->get_content('delete', $deletes);
		// 	} elseif (count($all_errors) > 0) {
		// 		$this->get_content('boeken', null, array('errors' => $all_errors));
		// 	} else {
		// 		header('Location: ' . get_request());
		// 	}
		// }
		
		// function _process_bestel() {
		// 	if ($this->_page_prepare(null, false) === false)
		// 		return;
			
		// 	$configuratie = get_model('DataModelConfiguratie');
		// 	$bestellen = $configuratie->get_value('boeken_bestellen');
			
		// 	if (!$bestellen) {
		// 		$this->get_content('deadline', null, null, false);
		// 		return;
		// 	}
			
		// 	$member_data = logged_in();
			
		// 	foreach ($_POST as $field => $value) {
		// 		if (strncmp($field, "boek_", 5) != 0)
		// 			continue;
				
		// 		$id = substr($field, 5);
		// 		$iter = $this->model->get_iter($id);
				
		// 		if (!$iter)
		// 			continue;
				
		// 		$bestelling = new DataIter($this->model, -1, 
		// 				array('lidid' => intval($member_data['id']),
		// 				'boekid' => intval($iter->get_id())));
					
		// 		$this->model->insert_bestelling($bestelling);
		// 	}
			
		// 	header('Location: ' . get_request());
		// }
		
		// function _process_unbestel() {
		// 	if ($this->_page_prepare(null, false) === false)
		// 		return;

		// 	$member_data = logged_in();

		// 	foreach ($_POST as $field => $value) {
		// 		if (strncmp($field, 'id_', 3) != 0)
		// 			continue;
				
		// 		$id = substr($field, 3);
		// 		$iter = $this->model->get_iter($id);
				
		// 		if (!$iter)
		// 			continue;
				
		// 		$bestelling = $this->model->get_bestelling($iter, $member_data['id']);

		// 		if (!$bestelling)
		// 			continue;

		// 		$this->model->delete_bestelling($bestelling);
		// 	}
			
		// 	header('Location: ' . get_request());
		// }
		
		// function _process_delete_bestellingen() {
		// 	if ($this->_page_prepare() === false)
		// 		return;
			
		// 	$this->model->delete_bestellingen();
		// 	header('Location: ' . get_request());
		// }
		
		// function _show_bestellingen() {
		// 	if (!member_in_commissie(COMMISSIE_BOEKCIE)) {
		// 		$this->get_content('boekcie', null, null, false);
		// 		return;
		// 	}
			
		// 	if (isset($_GET['order_by'])) {
		// 		$order = $_GET['order_by'];
				
		// 		if (!($order == 'boek' || $order == 'lid' || $order == 'prijs'))
		// 			$order = 'boek';
		// 	} else {
		// 		$order = 'boek';
		// 	}
			
		// 	$params = array('order_by' => $order);
			
		// 	if (isset($_GET['method'])) {
		// 		$method = $_GET['method'];

		// 		if (!isset($_GET['type'])) {
		// 			$view = 'bestellingen_' . $method;
		// 			$iters = $this->model->get_bestellingen($order);
		// 		} else {
		// 			if ($_GET['type'] == 'group_by_book') {
		// 				$view = 'bestellingen_' . $method . '_by_book';
		// 				$iters = $this->model->get_by_book();
		// 			} elseif ($_GET['type'] == 'group_by_member') {
		// 				$view = 'bestellingen_' . $method . '_by_member';
		// 				$iters = $this->model->get_by_member();
		// 			} else {
		// 				$view = 'bestellingen_' . $method;
		// 				$iters = $this->model->get_bestellingen($order); 
		// 			}
		// 		}

				
		// 		run_view('boeken::' . $view, $this->model, $iters, $params);
		// 	} else {
		// 		$this->get_content('bestellingen', $this->model->get_bestellingen($order), $params, false);	
		// 	}
		// }
		
		function run_impl() {
			// if (!logged_in())
			// 	$this->get_content('auth');
			// elseif (isset($_POST['submboekenadd']))
			// 	$this->_process_add();
			// elseif (isset($_POST['submboekenedit']))
			// 	$this->_process_edit();
			// elseif (isset($_POST['submboekenbestel']))
			// 	$this->_process_bestel();
			// elseif (isset($_POST['submboekenunbestel']))
			// 	$this->_process_unbestel();
			// elseif (isset($_POST['submboekendelbestellingen']))
			// 	$this->_process_delete_bestellingen();
			// elseif (isset($_GET['bestellingen']))
			// 	$this->_show_bestellingen();
			// else
			// 	$this->get_content('boeken', null, isset($_GET['added']) ? array('added' => true) : null);
			$this->get_content('go_to_webshop');
		}
	}
	
	$controller = new ControllerBoeken();
	$controller->run();
