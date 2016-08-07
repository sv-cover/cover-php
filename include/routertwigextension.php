<?php

class RouterTwigExtension extends Twig_Extension
{
	static public $routes = [
		'sessions' => [
			'login' => 'sessions.php?view=login',
			'logout' => 'sessions.php?view=logout',
			'sessions' => 'sessions.php?view=sessions',
			'overrides'=> 'sessions.php?view=overrides'
		],
		'profiel' => 'profiel.php',
		'editable' => [
			'update' => 'show.php?view=update&id=$editable[id]'
		]
	];

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

	static public function link_to($name, array $arguments = array())
	{
		$route = self::$routes;

		foreach (explode('.', $name) as $path) {
			if (!isset($route[$path]))
				throw new InvalidArgumentException("Route '$name' not found");
		
			$route = $route[$path];
		}

		$url = format_string($route, $arguments);
		
		return $url;
	}
}