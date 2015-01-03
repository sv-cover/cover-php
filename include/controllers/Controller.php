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
abstract class Controller
{
	protected $embedded = false;

	protected $model = null;

	protected function _form_is_submitted($form)
	{
		return $_SERVER['REQUEST_METHOD'] == 'POST';
			// && !empty($_POST['_' . $form . '_nonce'])
			// && in_array($_POST['_' . $form . '_nonce'], $_SESSION[$form . '_nonce']);
	}

	protected function run_view($view, DataModel $model = null, $iter = null, array $params = array())
	{
		list($view, $method) = explode('::', $view, 2);

		$view_class = sprintf('%sView', $view);

		$search_paths = array(
			'themes/' . get_theme() . '/views/' . $view . '/' . $view . '.php',
			'themes/default/views/' . $view . '/' . $view . '.php');

		$path = find_file($search_paths);

		if ($path === null)
			throw new RuntimeException("Could not find view class '$view_class' while trying to run view $view::$method.");

		include_once $path;

		$instance = new $view_class($this);

		call_user_func([$instance, $method], array_merge($params, compact('model', 'iter')));
	}

	protected function _get_title($iters = null)
	{
		return '';
	}

	protected function _get_view_name()
	{
		return strtolower(substr(get_class($this), strlen('Controller')));
	}

	protected function _get_default_view_params()
	{
		return array_merge(
			get_object_vars($this), // stuff like 'model' and other user defined stuff
			array('controller' => $this));
	}

	protected function _get_preferred_response()
	{
		return parse_http_accept($_SERVER['HTTP_ACCEPT'],
			array('application/json', 'text/html', '*/*'));
	}

	/** 
	  * Convenient function which runs the header view
	  * @var $params optional; the params to pass on to the header view
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
		if (!$this->embedded)
			$this->run_header(array('title' => $this->_get_title($iters)));

		if (strpos($view, '::') === false)
			$view = $this->_get_view_name() . '::' . $view;

		$this->run_view($view, $this->model, $iters, array_merge($this->_get_default_view_params(), $params));

		if (!$this->embedded)
			$this->run_footer();
	}

	public function link(array $arguments)
	{
		return sprintf('%s?%s', $_SERVER['SCRIPT_NAME'], http_build_query($arguments));
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
	
	abstract protected function run_impl();

	public function redirect($url, $permanent = false)
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

	public function run_exception(Exception $e)
	{
		if ($e instanceof NotFoundException)
			return $this->run_404_not_found($e);
		elseif ($e instanceof UnauthorizedException)
			return $this->run_401_unauthorized($e);
		else
			return $this->run_500_internal_server_error($e);
	}

	public function run_401_unauthorized(UnauthorizedException $exception)
	{
		header('Status: 401 Unauthorized');
		header('WWW-Authenticate: FormBased');
		$this->run_header(array('title' => __('Geen toegang')));
		run_view('common::auth', null, null, null);
		$this->run_footer();
	}

	public function run_404_not_found(NotFoundException $exception)
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

	public function run_500_internal_server_error(Exception $e)
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
