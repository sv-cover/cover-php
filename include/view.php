<?php

const ALLOW_SUBDOMAINS = 1;

/**
  * A Class implementing the default view. New views should subclass this one.
  * creating functions in the same directory as the view, with the extension .phtml
  * will allow a call to function_name().
  *
  */
class ViewNotFoundException extends RuntimeException {
	//
}

class TwigAccessor
{
	private $callback;

	public function __construct($callback)
	{
		$this->callback = $callback;
	}

	public function __call($argument, array $args)
	{
		return call_user_func($this->callback, $argument);
	}
}

class View
{ 	
	static public function byName($view, Controller $controller = null)
	{
		$possible_paths = [
			'themes/' . get_theme() . '/views/' . $view . '/' . $view . '.php',
			'themes/' . get_theme() . '/views/' . $view . '.php',
			'themes/default/views/' . $view . '/' . $view . '.php'
		];

		$file = find_file($possible_paths);

		if ($file === null)
			throw new ViewNotFoundException("Cannot find view $view");
				
		require_once($file);

		$view_name = $view . 'View';

		if (!class_exists($view_name))
			throw new RuntimeException("Expected the class $view_name in $file");

		$refl = new ReflectionClass($view_name);
		return $refl->newInstance($controller, dirname($file));
	}

	protected $controller;

	protected $layout;

	public function __construct(Controller $controller = null, $path = null)
	{
		// Default $path to @theme so View::render() is at least somewhat useful.
		if (!$path)
			$path = 'themes/' . get_theme() . '/views';

		$this->controller = $controller;

		// First look in our own view directory
		$loader = new Twig_Loader_Filesystem($path);

		// Then, look in the top level theme views directory
		$loader->addPath('themes/' . get_theme() . '/views', 'theme');

		// And add a shortcut to the layout directory through @layout
		$loader->addPath('themes/' . get_theme() . '/views/_layout', 'layout');

		$this->twig = new Twig_Environment($loader, array(
			'debug' => true,
			'strict_variables' => true,
		    'cache' => get_config_value('twig_cache', 'tmp/twig'),
		));

		require_once 'include/policytwigextension.php';
		$this->twig->addExtension(new PolicyTwigExtension());

		require_once 'include/i18ntwigextension.php';
		$this->twig->addExtension(new I18NTwigExtension());

		require_once 'include/routertwigextension.php';
		$this->twig->addExtension(new RouterTwigExtension());

		require_once 'include/HTMLtwigextension.php';
		$this->twig->addExtension(new HTMLTwigExtension());

		require_once 'themes/' . get_theme() . '/views/_layout/layout.php';
		$this->layout = new LayoutViewHelper();

		foreach ($this->_globals() as $key => $var)
			$this->twig->addGlobal($key, $var);
	}

	protected function _globals()
	{
		return [
			'view' => $this,
			'controller' => $this->controller,
			'model' => $this->controller ? $this->controller->model() : null,
			'policy' => $this->controller && $this->controller->model() ? get_policy($this->controller->model()) : null,
			'global' => [
				'auth' => get_auth(),
				'identity' => get_identity(),
				'db' => get_db(),
				'server' => $_SERVER,
				'GET' => $_GET,
				'POST' => $_POST,
				'i18n' => [
					'language' => i18n_get_language(),
					'languages' => i18n_get_languages()
				],
				'models' => new TwigAccessor(function($model) {
					return get_model('DataModel' . $model);
				}),
				'controllers' => new TwigAccessor(function($controller) {
					return null;
				}),
				'policies' => new TwigAccessor(function($model) {
					return get_policy('DataModel' . $model);
				})
			]
		];
	}

	public function stylesheets()
	{
		return [
			get_theme_data('style.css'),
			get_theme_data('styles/font-awesome.min.css'),
			'//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css'
		];
	}

	public function layout()
	{
		return $this->layout;
	}

	public function redirect($url, $permanent = false, $flags = 0)
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
		return '<a href="' . htmlentities($url, ENT_QUOTES) . '">' . __('Je wordt doorgestuurd. Klik hier om verder te gaan.') . '</a>';
	}

	public function render_401_unauthorized(UnauthorizedException $e) {
		header('Status: 401 Unauthorized');
		header('WWW-Authenticate: FormBased');
		return $e->getMessage();
	}

	public function render_404_not_found(NotFoundException $e) {
		header('Status: 404 Not Found');
		return $e->getMessage();
	}

	public function render($template_file, array $data = array())
	{
		$template = $this->twig->loadTemplate($template_file);

		return $template->render($data);
	}

	public function render_json(array $data)
	{
		header('Content-Type: application/json');
		return json_encode($data);
	}

	protected function _get_preferred_response()
	{
		return parse_http_accept($_SERVER['HTTP_ACCEPT'],
			array('application/json', 'text/html', '*/*'));
	}
}

class CRUDView extends View
{
	public function get_form_action(DataIter $iter = null)
	{
		return $iter && $iter->has_id()
			? $this->controller->link_to_update($iter)
			: $this->controller->link_to_create();
	}

	public function get_label(DataIter $iter = null, $create_label, $update_label)
	{
		return $iter && $iter->has_id() ? $update_label : $create_label;
	}

	public function render_delete(DataIter $iter, $success, $errors)
	{
		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				return $this->render_json(compact('errors'));

			default:
				if ($success)
					return $this->redirect($this->controller->link_to_index());
				else
					return $this->render('confirm_delete.twig', compact('iter', 'errors'));
		}
	}

	public function render_create(DataIter $iter, $success, $errors)
	{
		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				if ($success)
					return $this->_send_json_single($iter);
				else
					return $this->_send_json(compact('errors'));

			default:
				if ($success)
					return $this->redirect($this->controller->link_to_read($iter));
				else
					return $this->render('form.twig', compact('iter', 'errors'));
		}
	}

	public function render_read(DataIter $iter)
	{
		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				return $this->_send_json_single($iter);

			default:
				return $this->render('single.twig', compact('iter'));
		}
	}

	public function render_update(DataIter $iter, $success, $errors)
	{
		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				if ($success)
					return $this->_send_json_single($iter);
				else
					return $this->_send_json(compact('errors'));

			default:
				if ($success)
					return $this->redirect($this->controller->link_to_read($iter));
				else
					return $this->render('form.twig', compact('iter', 'errors'));
		}
	}

	public function render_index($iters)
	{
		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				return $this->_send_json_index($iters);

			default:
				return $this->render('index.twig', compact('iters'));
		}
	}

	protected function _send_json_single(DataIter $iter)
	{
		return $this->_send_json(array(
			'iter' => $this->_json_augment_iter($iter)
		));
	}

	protected function _send_json_index(array $iters)
	{
		$links = array();

		if (get_policy($this->controller->mode())->user_can_create())
			$links['create'] = $this->controller->link_to_create();

		return $this->_send_json(array(
			'iters' => array_map(array($this, '_json_augment_iter'), $iters),
			'_links' => $links
		));
	}

	protected function _json_augment_iter(DataIter $iter)
	{
		$links = array();

		$policy = get_policy($this->controller->model());

		if ($policy->user_can_read($iter))
			$links['read'] = $this->controller->link_to_read($iter);

		if ($policy->user_can_update($iter))
			$links['update'] = $this->controller->link_to_update($iter);

		if ($policy->user_can_delete($iter))
			$links['delete'] = $this->controller->link_to_delete($iter);

		return array_merge($iter->data, array('__id' => $iter->get_id(), '__links' => $links));
	}
}
