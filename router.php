<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

class Route 
{
	public function __construct($regexp, $redirect)
	{
		$this->regexp = $regexp;
		$this->redirect = $redirect;
	}

	public function isMatch($path)
	{
		return preg_match($this->regexp, $path);
	}

	public function invoke($path)
	{
		preg_match($this->regexp, $path, $match);

		$match = array_map('rawurlencode', $match);

		$destination = $this->_format($this->redirect, $match);

		$this->_redirect($destination);

		// $this->_run($destination);
	}

	private function _format($format, $data)
	{
		extract($data);
		return eval('return "' . addslashes($format) . '";');
	}

	private function _redirect($destination)
	{
		header('Location: ' . $destination);
	}

	private function _run($destination)
	{
		$query = parse_url($destination, PHP_URL_QUERY);
		parse_str($query, $_GET);

		$file = parse_url($destination, PHP_URL_PATH);
		$_SERVER['PHP_SELF'] = $file;
		include '.' . $file;
	}
}

$path = substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));

$routes = array(
	new Route('{^/committees/(?P<naam>\w+)$}', '/commissies.php?commissie=$naam'),
	new Route('{^/committees$}', '/commissies.php')
);

foreach ($routes as $route) {
	if ($route->isMatch($path)) {
		$route->invoke($path);
		exit;
	}
}

die('No route found for ' . $path);
