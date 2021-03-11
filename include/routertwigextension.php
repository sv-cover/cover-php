<?php

class RouterTwigExtension extends Twig_Extension
{
	public $routes;

	public function __construct()
	{
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
			'profiel' => [
				'read' => '/profiel.php?lid=$member[id]'
			],
			'editable' => [
				'update' => '/show.php?view=update&id=$editable[id]'
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

			new Twig_SimpleFunction('link_to',
				[$this, 'link_to_via_controller'],
				[
					'is_variadic' => true,
					'needs_context' => true
				])
		];
	}

	public function link_to_via_controller($context, $view, $iter = null, array $arguments = [])
	{
		return $context['controller']->link_to($view, $iter, $arguments);
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