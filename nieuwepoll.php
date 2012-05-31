<?php
	include('include/init.php');
	include('controllers/Controller.php');
	require_once('member.php');
	require_once('form.php');
	require_once('login.php');

	class ControllerNieuwePoll extends Controller {
		var $model = null;

		function ControllerNieuwePoll($commissie) {
			$this->model = get_model('DataModelPoll');
			$this->commissie = $commissie;
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => _('Poll toevoegen')));
			run_view('poll::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _page_prepare() {
			if (!member_in_commissie($this->commissie) &&
				!($this->commissie == COMMISSIE_EASY && $this->iter->get('sincelast') >= 14 && logged_in()))
				return false;
			
			return true;
		}

		function _view_nieuwe_poll($params = array()) {
			$params['commissie'] = $this->commissie;
			$this->get_content('nieuwepoll', null, $params);
		}
		
		function _process_add() {
			$errors = array();

			if (check_value_empty('titel', get_post('titel')) === false)
				$errors[] = 'titel';

			$opties = array();

			foreach ($_POST as $optie => $value) {
				if (strncmp($optie, 'optie_', 6) != 0)
					continue;
				
				if ($value != '')
					$opties[] = $value;
			}
			
			if (count($opties) == 0)
				$errors[] = 'optie_0';
			
			if (count($errors) > 0) {
				$this->_view_nieuwe_poll(array('errors' => $errors));
				return;
			}
			
			$member_data = logged_in();
			
			$iter = new DataIter($this->model, -1, 
					array(	'titel' => get_post('titel'),
						'commissieid' => $this->commissie,
						'door' => $member_data['id']));
			
			$id = $this->model->insert($iter, true);
			
			foreach ($opties as $optie) {
				$iter = new DataIter($this->model, -1,
					array(	'pollid' => $id,
						'optie' => $optie));
				
				$this->model->insert_optie($iter);
			}
			
			header('Location: ' . get_post('referer'));
		}

		function run_impl() {
			if (!$this->_page_prepare())
				$this->get_content('poll_auth');
			elseif (isset($_POST['submpollnieuw']))
				$this->_process_add();
			else
				$this->_view_nieuwe_poll();
		}
	}
	
	$controller = new ControllerNieuwePoll(isset($_GET['commissie']) ? intval($_GET['commissie']) : COMMISSIE_EASY);
	$controller->run();
?>
