<?php

const ALLOW_SUBDOMAINS = 1;
const ALLOW_EXTERNAL_DOMAINS = 2;

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

    protected $twig;

    /**
     * View constructor.
     * @param Controller|null $controller
     * @param null $path
     * @throws Twig_Error_Loader
     */
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

		$loader->addPath('themes/' . get_theme() . '/views/signup/fields', 'form_fields');

		$loader->addPath('themes/' . get_theme() . '/views/signup/configuration', 'form_configuration');

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

		require_once 'include/htmltwigextension.php';
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
				'views' => new TwigAccessor(function($view) {
					return View::byName($view);
				}),
				'policies' => new TwigAccessor(function($model) {
					return get_policy('DataModel' . $model);
				}),
				'config' => new TwigAccessor(function($key) {
					return get_config_value($key);
				})
			]
		];
	}

	public function scripts()
	{
		return [
			get_theme_data('assets/dist/js/cover.js'),
			get_theme_data('assets/dist/js/professionalism.js'),
		];
	}

	public function stylesheets()
	{
		$color_mode = $_COOKIE['cover_color_mode'] ?? 'light';
		if ($color_mode === 'dark')
			$base = [get_theme_data('assets/dist/css/cover-dark.css')];
		else
			$base = [get_theme_data('assets/dist/css/cover.css')];
		return array_merge($base, ['https://gitcdn.xyz/repo/pi0/clippyjs/master/assets/clippy.css']);
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

		if (($flags & ALLOW_EXTERNAL_DOMAINS)
			|| ($flags & ALLOW_SUBDOMAINS)
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
		return '<a href="' . htmlentities($url, ENT_QUOTES) . '">' . __('You are being redirected. Click here to continue.') . '</a>';
	}

	public function render_401_unauthorized(UnauthorizedException $e) {
		header('Status: 401 Unauthorized');
		header('WWW-Authenticate: FormBased');
		return $this->render('@layout/401_unauthorized.twig', ['exception' => $e]);
	}

	public function render_404_not_found(NotFoundException $e) {
		header('Status: 404 Not Found');
		return $this->render('@layout/404_not_found.twig', ['exception' => $e]);
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
		return isset($_SERVER['HTTP_ACCEPT'])
			? parse_http_accept($_SERVER['HTTP_ACCEPT'], ['application/json', 'text/html', '*/*'])
			: 'text/html';
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
					return $this->render_json(compact('errors'));

			default:
				if ($success)
					return $this->redirect($this->controller->link_to_read($iter));
				else
					return $this->render('form.twig', compact('iter', 'errors'));
		}
	}

	public function render_read(DataIter $iter, array $extra = [])
	{
		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				return $this->_send_json_single($iter);

			default:
				return $this->render('single.twig', array_merge($extra, compact('iter')));
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
					return $this->render_json(compact('errors'));

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
		return $this->render_json(array(
			'iter' => $this->_json_augment_iter($iter)
		));
	}

	protected function _send_json_index(array $iters)
	{
		$links = array();

		$new_iter = $this->controller->model()->new_iter();

		if (get_policy($new_iter)->user_can_create($new_iter)) 
			$links['create'] = $this->controller->json_link_to_create();

		return $this->render_json(array(
			'iters' => array_map(array($this, '_json_augment_iter'), $iters),
			'__links' => $links
		));
	}

	protected function _json_augment_iter(DataIter $iter)
	{
		$links = array();

		$policy = get_policy($this->controller->model());

		if ($policy->user_can_read($iter))
			$links['read'] = $this->controller->json_link_to_read($iter);

		if ($policy->user_can_update($iter))
			$links['update'] = $this->controller->json_link_to_update($iter);

		if ($policy->user_can_delete($iter))
			$links['delete'] = $this->controller->json_link_to_delete($iter);

		if (method_exists($this->controller, 'get_data_for_iter'))
			$data = $this->controller->get_data_for_iter($iter);
		else
			$data = $iter->data;

		return array_merge($data, array('__id' => $iter->get_id(), '__links' => $links));
	}
}
