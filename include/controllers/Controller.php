<?php
	if (!defined('IN_SITE'))
		return;

	require_once('functions.php');

	/** 
	  * A class implementing the simplest controller. This class provides
	  * viewing a simple static page by running the header view, then
	  * the specified view and then the footer view
	  */
	class Controller {
		var $view;
		var $model;
		var $iter;

		/** 
		  * Controller constructor
		  * @view the view to show
		  * @model optional; the model to pass on to the view
		  * @iter optional; the iter to pass on to the view
		  * @params optional; the params to pass on to the view
		  */
		function Controller($view, $model = null, $iter = null, $params = null) {
			$this->view = $view;
			$this->model = $model;
			$this->iter = $iter;
		}
		
		/** 
		  * Convenient function which runs the header view
		  * @params optional; the params to pass on to the header view
		  */
		function run_header($params = null) {
			run_view('header', null, null, $params);
		}

		/** 
		  * Convenient function which runs the footer view
		  * @params optional; the params to pass on to the footer view
		  */
		function run_footer($params = null) {
			run_view('footer', null, null, $params);
		}
	
		/** 
		  * Function which shows the page. It first runs the header,
		  * then the view specified in the constructor and finally
		  * the footer
		  */
		function get_content() {
			$this->run_header(Array('title' => ucfirst($this->view)));
			run_view($this->view, $this->model, $this->iter, $this->params);
			$this->run_footer();
		}
	
		/** 
		  * Run the controller
		  */
		function run() {
			ob_start();
			
			try {
				$this->run_impl();
			}
			catch(Exception $e) {
				$this->run_exception($e);
			}
			
			ob_end_flush();
		}
		
		function run_impl() {
			$this->get_content();
		}

		function run_exception(Exception $e)
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
?>
