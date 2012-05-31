<?php
	if (!defined('IN_SITE'))
		return;

	require_once('Controller.php');

	/** 
	  * A class implementing the poll controller. This class 
	  * is a full controller for pages including a poll and can be used 
	  * by other controllers to embed a poll. The embedding is fully
	  * automated and the controller embedding this controller only
	  * needs to instantiate and run a #ControllerPoll (great!)
	  */
	class ControllerPoll extends Controller {
		var $model = null;
		var $commissie = null;
		var $iter = null;

		/**
		  * ControllerPoll constructor
		  * @commissie the commissie id to get the latest poll for
		  */
		function ControllerPoll($commissie) {
			$this->model = get_model('DataModelPoll');
			$this->commissie = $commissie;

			$this->iter = $this->model->get_for_commissie($this->commissie);
		}

		/**
		  * Get the poll content. This function does
		  * not run the header or the footer view since the class
		  * is meant to be embedded.
		  * @view the poll::view to get
		  * @ite the iter to pass on to the view
		  * @params optional; the params to pass on to the view
		  */
		function get_content($view, $iter, $params = null) {
			return run_view('poll::' . $view, $this->model, $iter, $params);
		}
		
		function _voted() {
			return (isset($_COOKIE['voted'][$this->iter->get_id()]) || 
					$this->model->voted($this->iter->get_id()));
		}
		
		function _can_new() {
			return member_in_commissie($this->commissie) || 
				($this->commissie == COMMISSIE_EASY && $this->iter->get('sincelast') >= 14 && logged_in());
		}
		
		function _process_vote() {
			if (!isset($_POST['optie']) || $_POST['optie'] === '')
				return;
			
			if ($this->_voted())
				return;

			$this->model->vote($_POST['optie']);
			
			if (!logged_in()) {
				setcookie("voted[" . $this->iter->get_id(). "]", '1', time() + 60 * 60 * 24 * 30);
				
				if (!isset($_COOKIE['voted']))
					$_COOKIE['voted'] = array();

				$_COOKIE['voted'][$this->iter->get_id()] = '1';
			}
		}
		
		function _view_poll() {
			$params = array('voted' => $this->_voted());
			
			if ($this->_can_new())
				$params['enable_new'] = true;

			return $this->get_content('poll', $this->iter, $params);
		}
		
		function run() {
			if (isset($_POST['submpollvote']) && $_POST['submpollvote'] == $this->iter->get_id())
				$this->_process_vote();
			
			return $this->_view_poll();
		}
	}
?>
