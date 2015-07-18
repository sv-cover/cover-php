<?php
	require_once 'include/init.php';
	require_once 'include/controllers/Controller.php';
	
	class Controllerfoto extends Controller
	{
		public function __construct()
		{
			$this->model = get_model('DataModelMember');
		}
		
		protected function get_content($view, $iter = null, $params = null)
		{
			// Don't run header and footer
			run_view('foto::' . $view, $this->model, $iter, $params);
		}
		
		protected function run_impl()
		{
			$iter = $this->model->get_iter($_GET['lid_id']);

			if ($this->model->is_private($iter, 'foto'))
				$this->get_content('incognito');
			elseif (!$this->model->has_picture($iter))
				$this->get_content('geenfoto');
			elseif (isset($_GET['get_thumb']))
				$this->get_content('getthumb', $iter);
			else
				$this->get_content('getphoto', $iter);
		}
	}
	
	$controller = new Controllerfoto();
	$controller->run();
