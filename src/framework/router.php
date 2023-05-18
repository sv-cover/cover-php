<?php

use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\RedirectableUrlMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;


function get_router()
{
	static $router;

	if (!isset($router)) {
		$file_locator = new FileLocator([__DIR__ . '/..']);
		$loader = new YamlFileLoader($file_locator);

		$context = new RequestContext();
		$context->fromRequest(get_request());

		$router = new Router(
			$loader,
			'routes.yaml',
			[
				'matcher_class' => RedirectableCompiledUrlMatcher::class,
				'cache_dir' => get_config_value('routing_cache'),
			],
			$context
		);
	}

	return $router;
}

function get_request()
{
	static $request;

	if (!isset($request))
		$request = Request::createFromGlobals();

	return $request;
}


/**
  * Routing helper to make route matching agnostic to trailing slashes
  */
class RedirectableCompiledUrlMatcher extends CompiledUrlMatcher implements RedirectableUrlMatcherInterface
{
	public function redirect(string $path, string $route, string $scheme = null): array
	{
		return [
			'_controller' => 'App\\Controller\\RedirectController',
			'path' => $path,
			'permanent' => true,
			'_route' => $route,
		];
	}
}
