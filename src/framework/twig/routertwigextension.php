<?php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RouterTwigExtension extends Twig_Extension
{
	public $routes;

	public function __construct($router)
	{
		$this->router = $router;
	}

	public function getName()
	{
		return 'router';
	}

	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('path', [$this, 'get_path']),
			new Twig_SimpleFunction('url', [$this, 'get_url']),
			new Twig_SimpleFunction('login_path', [$this, 'get_login_path']),
			new Twig_SimpleFunction('logout_path', [$this, 'get_logout_path']),
			new Twig_SimpleFunction('static_path', [$this, 'get_static_path']),
			new Twig_SimpleFunction('link_static', 'get_theme_data'),
		];
	}

	/* Analogous to Symfony's Twig function 'path' */
	public function get_path(string $name, array $parameters = [], bool $relative = false)
	{
		$reference_type = $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH;
		return $this->router->generate($name, $parameters, $reference_type);
	}

	/* Analogous to Symfony's Twig function 'url' */
	public function get_url(string $name, array $parameters = [], bool $scheme_relative = false)
	{
		$reference_type = $scheme_relative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL;
		return $this->router->generate($name, $parameters, $reference_type);
	}

	public function get_login_path($referrer = null, $name = 'login')
	{
		if (!isset($referrer))
			$referrer = $_SERVER['REQUEST_URI'];

		return $this->get_path($name, compact('referrer'));
	}

	public function get_logout_path($referrer = null, $name = 'logout')
	{
		if (!isset($referrer))
			$referrer = $_SERVER['REQUEST_URI'];

		return $this->get_path($name, compact('referrer'));
	}

	public function get_static_path(string $file, bool $include_filemtime = true)
	{
		return get_theme_data($file, $include_filemtime);
	}
}