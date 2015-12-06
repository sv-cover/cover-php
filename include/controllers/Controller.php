<?php
	if (!defined('IN_SITE'))
		return;

	require_once 'include/functions.php';
	require_once 'include/markup.php';

	const ALLOW_SUBDOMAINS = 1;

	/** 
	  * A class implementing the simplest controller. This class provides
	  * viewing a simple static page by running the header view, then
	  * the specified view and then the footer view
	  */
	class Controller {
		protected $view;
		protected $model;
		protected $iter;
		protected $params;
		protected $embedded;

		/** 
		  * Controller constructor
		  * @view the view to show
		  * @model optional; the model to pass on to the view
		  * @iter optional; the iter to pass on to the view
		  * @params optional; the params to pass on to the view
		  */
		public function Controller($view, $model = null, $iter = null, $params = null) {
			$this->view = $view;
			$this->model = $model;
			$this->iter = $iter;
		}
		
		/** 
		  * Convenient function which runs the header view
		  * @params optional; the params to pass on to the header view
		  */
		protected function run_header($params = null) {
			run_view('header', null, null, $params);
		}

		/** 
		  * Convenient function which runs the footer view
		  * @params optional; the params to pass on to the footer view
		  */
		protected function run_footer($params = null) {
			run_view('footer', null, null, $params);
		}
	
		/** 
		  * Function which shows the page. It first runs the header,
		  * then the view specified in the constructor and finally
		  * the footer
		  */
		protected function get_content($view, $iters = null, array $params = array())
		{
			$this->run_header(array('title' => ucfirst($this->view)));
			run_view($view, $this->model, $iters, $params);
			$this->run_footer();
		}
	
		/** 
		  * Run the controller
		  */
		public function run()
		{
			ob_start();
			
			try {
				$this->run_impl();
			}
			catch(Exception $e) {
				$this->run_exception($e);
			}
			
			ob_end_flush();
		}

		public function run_embedded()
		{
			ob_start();
			
			$this->embedded = true;

			try {
				$this->run_impl();
			}
			catch(Exception $e) {
				$this->run_exception($e);
			}

			$this->embedded = false;

			return ob_get_clean();
		}
		
		protected function run_impl()
		{
			$this->get_content();
		}

		protected function redirect($url, $permanent = false, $flags = 0)
		{
			// parse and selectively rebuild the url to prevent
			// weird tricks where a custom form redirects you to
			// outside the Cover website.
			$parts = parse_url($url);

			$url = '';

			if (($flags & ALLOW_SUBDOMAINS)
				&& isset($parts['host'])
				&& is_same_domain($parts['host'], $_SERVER['HTTP_HOST'])) {
				$url = '//' . $parts['host'];
			}

			$url .= $parts['path'];

			if (isset($parts['query']))
				$url .= '?' . $parts['query'];

			if (isset($parts['fragment']))
				$url .= '#' . $parts['fragment'];

			if ($permanent)
				header('Status: 301 Moved Permanently');

			header('Location: ' . $url);
			echo '<a href="' . htmlentities($url, ENT_QUOTES) . '">' . __('Je wordt doorgestuurd. Klik hier om verder te gaan.') . '</a>';
			exit;
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
			header('Status: 401 Unauthorized');
			header('WWW-Authenticate: FormBased');
			$this->run_header(array('title' => __('Geen toegang')));
			run_view('common::auth', null, null, null);
			$this->run_footer();
		}

		protected function run_404_not_found(NotFoundException $exception)
		{
			try {
				header('Status: 404 Not Found');
				$this->run_header(Array('title' => ucfirst($this->view)));
				run_view('common::not_found', null, null, array('details' => $exception->getMessage()));
				$this->run_footer();
			} catch (Exception $e) {
				$this->run_500($e);
			}
		}

		protected function run_500_internal_server_error(Exception $e)
		{
			header('Status: 500 Interal Server Error');

			if (get_config_value('show_exceptions'))
				echo '<pre>' . $e . '</pre>';
			else {
				ob_clean();
				echo __('Sorry, er ging iets verschrikkelijk mis. Probeer het later nog eens of mail de WebCie (webcie@svcover.nl)');
			}
		}

		protected function _send_json($data)
		{
			header('Content-Type: application/json');
			echo json_encode($data, JSON_PRETTY_PRINT);
		}
	}
