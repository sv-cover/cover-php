<?php
	include('include/init.php');
	include('controllers/Controller.php');
	include('controllers/ControllerEditable.php');

	require_once('login.php');
	require_once('form.php');

	class ControllerAfstudeerplaatsen extends Controller {
		var $model = null;

		function ControllerAfstudeerplaatsen() {
			$this->model = get_model('DataModelBedrijven');
		}
		
		function get_content($view, $iter = null, $params = null, $show_editable = true) {
			$this->run_header(array('title' => _('Afstudeerplaatsen')));
			
			if ($show_editable) {
				$controller = new ControllerEditable('Afstudeerplaatsen');
				$controller->run();
			}
			
			run_view('afstudeerplaatsen::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _view_afstudeerplaatsen($params = null) {
			$iters = $this->model->get();
			
			$this->get_content('afstudeerplaatsen', $iters, $params);
		}
		
		function _view_bedrijf($id) {
			$iter = $this->model->get_iter($id);
			
			if (!$iter)
				$this->get_content('bedrijf_not_found', null, null, false);
			else
				$this->get_content('bedrijf', $iter, null, false);
		}
		
		function _view_edit_bedrijf($id, $params = null) {
			if (!is_object($id))
				$iter = $this->model->get_iter($id);
			else
				$iter = $id;
			
			if (!$iter)
				$this->get_content('bedrijf_not_found', null, null, false);
			else
				$this->get_content('bedrijf_edit', $iter, $params, false);		
		}
		
		function _view_edit_plaats($id, $params = null) {
			if (!is_object($id))
				$iter = $this->model->get_afstudeerplaats($id);
			else
				$iter = $id;
			
			if (!$iter)
				$this->get_content('plaats_not_found', null, null, false);
			else
				$this->get_content('plaats_edit', $iter, $params, false);
		}
		
		function _page_prepare() {
			if (!member_in_commissie(COMMISSIE_BESTUUR) && !member_in_commissie(COMMISSIE_PRCIE)) {
				$this->get_content('auth_commissie');
				return false;
			}
			
			return true;
		}	
		
		function _check_bedrijf_data(&$errors) {
			$data = check_values(array(
				'naam',
				'adres',
				'postcode',
				'website',
				'contactpersoon',
				'telefoonnummer',
				'beschrijving'
				), $errors);
			
			$data['email'] = get_post('email');
			
			return $data;
		}
		
		function _process_edit_bedrijf($id) {
			if (!$this->_page_prepare())
				return;

			$iter = $this->model->get_iter($id);
			
			if (!$iter) {
				$this->get_content('bedrijf_not_found', null, null, false);
				return;				
			}
			
			$data = $this->_check_bedrijf_data($errors);

			if (count($errors) > 0) {
				$this->_view_edit_bedrijf($id, array('errors' => $errors));
				return;	
			}
			
			$iter->set_all($data);
			$this->model->update($iter);
			
			header('Location: afstudeerplaatsen.php#bedrijf_' . $id);		
		}
		
		function _process_add_bedrijf() {
			if (!$this->_page_prepare())
				return;
			
			$data = $this->_check_bedrijf_data($errors);
			
			if (count($errors) > 0) {
				$this->_view_afstudeerplaatsen(array('errors' => $errors, 'expand' => true));
				return;	
			}
			
			$iter = new DataIter($this->model, -1, $data);
			
			$this->model->insert($iter);
			header('Location: afstudeerplaatsen.php');
		}

		function _check_plaats_data(&$errors) {
			return check_values(array(
				'opdracht',
				'beschrijving'				
				), $errors);		
		}

		function _process_edit_plaats($id) {
			if (!$this->_page_prepare())
				return;
			
			$iter = $this->model->get_afstudeerplaats($id);
			
			if (!$iter) {
				$this->get_content('plaats_not_found', null, null, false);
				return;
			}
			
			$data = $this->_check_plaats_data($errors);

			if (count($errors) > 0) {
				$this->_view_edit_plaats($id, array('errors' => $errors));
				return;	
			}
			
			$iter->set_all($data);
			$this->model->update_afstudeerplaats($iter);
			
			header('Location: afstudeerplaatsen.php?bedrijf=' . $iter->get('bedrijf'));
		}
		
		function _process_add_plaats() {
			if (!$this->_page_prepare())
				return;
			
			$iter = $this->model->get_iter(get_post('bedrijf'));
			
			if (!$iter) {
				$this->get_content('bedrijf_not_found', null, null, false);
				return;
			}
			
			$data = $this->_check_plaats_data($errors);

			if (count($errors) > 0) {
				$this->get_content('bedrijf', $iter, array('errors' => $errors, 'expand' => true), false);
				return;	
			}

			$data['bedrijf'] = intval(get_post('bedrijf'));
			$iter = new DataIter($this->model, -1, $data);

			$this->model->insert_afstudeerplaats($iter);
			header('Location: ' . add_request(get_request(), 'bedrijf=' . $iter->get('bedrijf')));
		}
		
		function _process_del_bedrijf($id) {
			if (!$this->_page_prepare())
				return;
			
			$iter = $this->model->get_iter($id);
			
			if (!$iter) {
				$this->get_content('bedrijf_not_found', null, null, false);
				return;
			}
			
			$this->model->delete($iter);
			header('Location: ' . get_request('delbedrijf'));
		}

		function _process_del_plaats($id) {
			if (!$this->_page_prepare())
				return;
			
			$iter = $this->model->get_afstudeerplaats($id);
			
			if (!$iter) {
				$this->get_content('plaats_not_found', null, null, false);
				return;
			}
			
			$this->model->delete_afstudeerplaats($iter);
			header('Location: ' . add_request(get_request('delplaats'), 'bedrijf=' . $iter->get('bedrijf')));
		}
		
		function run_impl() {

			if (isset($_POST['submbedrijf_add']))
				$this->_process_add_bedrijf();
			elseif (isset($_POST['submplaats_add']))
				$this->_process_add_plaats();
			elseif (isset($_POST['submbedrijf_edit']))
				$this->_process_edit_bedrijf(get_post('id'));
			elseif (isset($_POST['submplaats_edit']))
				$this->_process_edit_plaats(get_post('id'));
			elseif (isset($_GET['delbedrijf']))
				$this->_process_del_bedrijf($_GET['delbedrijf']);
			elseif (isset($_GET['delplaats']))
				$this->_process_del_plaats($_GET['delplaats']);
			elseif (isset($_GET['editbedrijf']))
				$this->_view_edit_bedrijf($_GET['editbedrijf']);
			elseif (isset($_GET['editplaats']))
				$this->_view_edit_plaats($_GET['editplaats']);
			elseif (isset($_GET['bedrijf']))
				$this->_view_bedrijf($_GET['bedrijf']);
			else
				$this->_view_afstudeerplaatsen();
		}
	}
	
	$controller = new ControllerAfstudeerplaatsen();
	$controller->run();
?>
