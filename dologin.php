<?php
	require_once 'include/init.php';
	require_once 'include/login.php';
	require_once 'include/controllers/Controller.php';
	
	class ControllerLogin extends Controller
	{
		function run_impl()
		{
			$referer = $_POST['referer'];

			if (get_post('email') && get_post('password')) {
				if (login(get_post('email'), get_post('password'), get_post('remember') == 'yes'))
					$this->redirect($referer);
				else
					$this->redirect(add_request($referer, 'error=login'));
			}
			
			if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'])
				header('Location: ' . $_SERVER['HTTP_REFERER']);
			else
				header('Location: index.php');
		}
	}

	$controller = new ControllerLogin(null);
	$controller->run();
