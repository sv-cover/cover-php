<?php

class RouterTwigExtension extends Twig_Extension
{
	public $routes;

	public function __construct()
	{
		$this->routes = [
			'sessions' => [
				'login' => function($args) {
					return edit_url('sessions.php?view=login',
						['referrer' => isset($args['referrer'])
							? $args['referrer']
							: $_SERVER['REQUEST_URI']]);
				},
				'logout' => 'sessions.php?view=logout',
				'sessions' => 'sessions.php?view=sessions',
				'overrides'=> function($args) {
					return edit_url('sessions.php?view=overrides', $args);
				}
			],
			'profiel' => [
				'read' => 'profiel.php?lid_id=$member[id]'
			],
			'editable' => [
				'update' => 'show.php?view=update&id=$editable[id]'
			],
			'foto' => [
				'portrait' => 'foto.php?format=portrait&width=$width&lid_id=$member[id]',
				'square' => 'foto.php?format=square&width=$width&lid_id=$member[id]'
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
			new Twig_SimpleFunction('link', [$this, 'link_to'], ['is_variadic' => true, 'is_safe' => ['html', 'html_attr']])
		];
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