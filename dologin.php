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
				login(get_post('email'), md5(get_post('password')), get_post('remember') == 'yes');
				header("Location: $referer");
				exit();
			}
			
			if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'])
				header('Location: ' . $_SERVER['HTTP_REFERER']);
			else
				header('Location: index.php');
		}
	}

	$controller = new ControllerLogin();
	$controller->run();
