<?php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouterTwigExtension extends Twig_Extension
{
	public $routes;
	protected $router;

	public function __construct($router)
	{
		$this->router = $router;
		$this->routes = [
			'sessions' => [
				'login' => function($args) {
					return edit_url('/sessions.php?view=login',
						['referrer' => isset($args['referrer'])
							? $args['referrer']
							: $_SERVER['REQUEST_URI']]);
				},
				'logout' => function($args) {
					return edit_url('/sessions.php?view=logout', $args);
				},
				'sessions' => '/sessions.php?view=sessions',
				'overrides'=> function($args) {
					return edit_url('/sessions.php?view=overrides', $args);
				}
			],
			'foto' => [
				'portrait' => '/foto.php?format=portrait&width=$width&lid_id=$member[id]',
				'square' => '/foto.php?format=square&width=$width&lid_id=$member[id]'
			]
		];
	}

	public function getName()
	{
		return 'router';
	}

	public function getFunctions()
	{
		return [
			// The old experiment
			new Twig_SimpleFunction('link', [$this, 'link_to'], ['is_variadic' => true]),
			new Twig_SimpleFunction('path', [$this, 'get_path']),
			new Twig_SimpleFunction('url', [$this, 'get_url']),
		];
	}

	/* Analogous to Symfony's Twig function 'path' */
	public function get_path(string $name, array $parameters = [], bool $relative = false)
	{
		$reference_type = $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH;
		return $this->router->generate($name, $parameters, $reference_type);
	}

	/* Analogous to Symfony's Twig function 'url' */
	public function get_url(string $name, array $parameters = [], bool $schemeRelative = false)
	{
		$reference_type = $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL;
		return $this->router->generate($name, $parameters, $reference_type);
	}

	public function link_to($name, array $arguments = array())
	{
		$route = $this->routes;

		foreach (explode('.', $name) as $path) {
			if (!isset($route[$path]))
				throw new InvalidArgumentException("Route '$name' not found");
		
			$route = $route[$path];
		}

		if (is_callable($route))
			$url = call_user_func($route, $arguments);
		else
			$url = format_string($route, $arguments);
		
		return $url;
	}
}