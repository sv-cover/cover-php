<?php

	include('include/init.php');
	include('controllers/Controller.php');
	require_once('login.php');

	class ControllerLogin extends Controller {
		function ControllerLogin() {
		}
		
		function run_impl() {
			$referer = $_POST['referer'];

			if (get_post('email') && get_post('pass')) {
				login(get_post('email'), md5(get_post('pass')), get_post('remember') == 'yes');
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
?>
