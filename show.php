<?php
	require_once 'include/init.php';
	require_once 'include/form.php';
	require_once 'include/controllers/Controller.php';
	require_once 'include/controllers/ControllerEditable.php';
	
	class ControllerShow extends Controller
	{
		function ControllerShow() {
			$this->model = get_model('DataModelEditable');
		}
		
		function get_page_content($id)
		{
			$params = compact('id');

			$iter = $this->model->get_iter($id);

			if (!$iter)
				return $this->get_content('common::not_found');

			$params['title'] = $iter->get_title();

			$this->run_header($params);

			run_view('show::single', $this->model, $iter, $params);

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

		protected function _is_embedded_page($page_id, $model)
		{
			$model = get_model($model);
			return $model->get_from_page($page_id);
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
			elseif ($committee = $this->_is_embedded_page($_GET['id'], 'DataModelCommissie'))
				$this->redirect('commissies.php?id=' . $committee->get('login'), true);
			elseif ($board = $this->_is_embedded_page($_GET['id'], 'DataModelBesturen'))
				$this->redirect('besturen.php#' .  rawurlencode($board->get('login')), true);
			else
				$this->get_page_content($_GET['id']);
		}
	}
	
	$controller = new ControllerShow();
	$controller->run();
