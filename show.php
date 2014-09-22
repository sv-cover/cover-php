<?php
	include('include/init.php');
	include('controllers/Controller.php');
	include('controllers/ControllerEditable.php');
	require_once('include/form.php');
	
	class ControllerShow extends Controller {
		function ControllerShow() {
			$this->model = get_model('DataModelEditable');
		}
		
		function get_page_content($id)
		{
			$params = array();

			if ($title = $this->model->get_title($id))
				$params['title'] = $title;

			$this->run_header($params);

			$editable = new ControllerEditable($id);
			$editable->run();

			$this->run_footer();
		}
		
		function get_content($view, $params = null) {
			$this->run_header(array('title' => __('Show')));
			run_view($view, $this->model, null, $params);
			$this->run_footer();
		}
		
		function _prepare_page() {
			if (!member_in_commissie(COMMISSIE_BESTUUR)
				&& !member_in_commissie(COMMISSIE_KANDIBESTUUR)) {
				$this->get_content('common::auth');
				return false;
			} else {
				return true;
			}
		}
		
		function _view_new() {
			if (!$this->_prepare_page())
				return;
				
			$this->get_content('show::edit');
		}
		
		function _check_values() {
			/* Check/format all the items */
			$errors = array();
			$data = check_values(
				array(
					'titel', 
					'content',
					array('name' => 'owner', 'function' => 'check_value_toint')),
				$errors);

			if (count($errors) != 0) {
				$this->get_content('show::edit', array('errors' => $errors));
				return false;
			}

			return $data;
		}
		
		function _process_new() {
			if (!$this->_prepare_page())
				return;
			
			if (($data = $this->_check_values()) === false)
				return;
				
			$iter = new DataIter($this->model, -1, $data);
			$id = $this->model->insert($iter, true);
			$_SESSION['alert'] = sprintf(__('Er is een nieuwe pagina aangemaakt met het id %d.'), $id);

			header('Location: show.php?id=' . $id);
			exit();
		}
		
		function _view_preview() {
			ob_end_clean();
			
			$language = get_post('editable_language');
			$field = 'content';
			
			if ($language != 'nl')
				$field .= '_' . $language;

			$iter = $this->model->get_iter(get_post('editable_id'));

			if (!$data)
				$data = get_post($field);
			
			$iter->set($field, $data);
			
			$params = array('field' => $field);

			run_view('show::preview', $this->model, $iter, $params);
			exit();
		}
		
		function run_impl() {
			if (isset($_GET['preview']))
				$this->_view_preview();
			elseif (isset($_POST['submshownew']))
				$this->_process_new();
			elseif (isset($_GET['show_new']))
				$this->_view_new();
			elseif (!isset($_GET['id']))
				$this->get_page_content(-1);
			else
				$this->get_page_content($_GET['id']);
		}
	}
	
	$controller = new ControllerShow();
	$controller->run();
?>
