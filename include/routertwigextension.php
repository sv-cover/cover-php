<?php

class RouterTwigExtension extends Twig_Extension
{
	static public $routes = [
		'sessions' => [
			'login' => 'sessions.php?view=login&referer=$referer|rawurlencode'
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

		foreach (explode('.', $name) as $path)
			$route = $route[$path];

		return format_string($route, $arguments);
	}
}