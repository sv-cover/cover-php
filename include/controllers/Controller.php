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
		protected function get_content() {
			$this->run_header(Array('title' => ucfirst($this->view)));
			run_view($this->view, $this->model, $this->iter, $this->params);
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

		protected function redirect($url, $permanent = false)
		{
			// parse and selectively rebuild the url to prevent
			// weird tricks where a custom form redirects you to
			// outside the Cover website.
			$parts = parse_url($url);

			$url = $parts['path'];

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
			header('Status: 500 Interal Server Error');

			if (get_config_value('show_exceptions'))
				echo '<pre>' . $e . '</pre>';
			else {
				ob_clean();
				echo __('Sorry, er ging iets verschrikkelijk mis. Probeer het later nog eens of mail de WebCie (webcie@svcover.nl)');
			}
		}
	}
