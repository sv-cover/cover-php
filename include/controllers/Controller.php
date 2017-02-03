<?php
	if (!defined('IN_SITE'))
		return;

	require_once 'include/functions.php';
	require_once 'include/markup.php';

	/** 
	  * A class implementing the simplest controller. This class provides
	  * viewing a simple static page by running the header view, then
	  * the specified view and then the footer view
	  */
	class Controller
	{
		protected $view;

		protected $model;

		protected $routes = array();

		public function view()
		{
			return $this->view;
		}
		
		public function model()
		{
			return $this->model;
		}
		
		public function run()
		{
			try {
				echo $this->run_impl();
			}
			catch (Exception $e) {
				echo $this->run_exception($e);
			}
			catch (TypeError $e) {
				echo $this->run_500_stupid_programmar($e);
			}
		}

		protected function run_impl()
		{
			$path = substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));

			if (!$path)
				$path = '/';

			foreach ($this->routes as $route => $callback)
			 	if (preg_match('{^' . $route . '$}i', $path, $match))
			 		return call_user_func_array($callback, array_slice($match, 1));

			throw new NotFoundException('No route for path "' . $path . '"');
		}

		protected function run_exception(Exception $e)
		{
			if ($e instanceof NotFoundException)
				return $this->run_404_not_found($e);
			elseif ($e instanceof UnauthorizedException)
				return $this->run_401_unauthorized($e);
			else
				return $this->run_500_internal_server_error($e);
		}

		protected function run_401_unauthorized(UnauthorizedException $exception)
		{
			return $this->view()->render_401_unauthorized($exception);
		}

		protected function run_404_not_found(NotFoundException $exception)
		{
			return $this->view()->render_404_not_found($exception);
		}

		protected function run_500_internal_server_error(Exception $e)
		{
			if (!headers_sent())
				header('Status: 500 Interal Server Error');

			if (get_config_value('show_exceptions'))
				return '<pre>' . $e . '</pre>';
			else {
				return __('Sorry, er ging iets verschrikkelijk mis. Probeer het later nog eens of mail de WebCie (webcie@svcover.nl)');
			}
		}

		protected function run_500_stupid_programmar(TypeError $e)
		{
			if (!headers_sent())
				header('Status: 500 Interal Server Error');

			if (get_config_value('show_exceptions'))
				return '<pre>' . $e . '</pre>';
			else {
				return __('Sorry, een (oud) AC/DCee-lid kan voor geen meter programmeren. Als je daar vaker last van hebt, vertel het door aan de commissie (webcie@svcover.nl) zodat ze taart van die persoon kunnen eisen.');
			}
		}

		protected function _form_is_submitted($action, $arg0 = null)
		{
			// Turn _form_is_submitted('delete', iter) to 'delete_24'
			$args = array_slice(func_get_args(), 1);
			$action_name = nonce_action_name($action, $args);

			$nonce = null;

			if (!empty($_POST['_nonce']))
				$nonce = $_POST['_nonce'];
			else if (!empty($_GET['_nonce']))
				$nonce = $_GET['_nonce'];

			$answer = $_SERVER['REQUEST_METHOD'] == 'POST'
				&& $nonce !== null
				&& nonce_verify($nonce, $action_name);

			return $answer;
		}

		public function link(array $arguments)
		{
			return sprintf('%s?%s', $_SERVER['SCRIPT_NAME'], http_build_query($arguments));
		}

		final protected function get_content()
		{
			throw new LogicException("Controller::get_content is no longer accepted");
		}
	}
