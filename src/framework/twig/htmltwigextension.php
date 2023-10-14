<?php
require_once 'src/framework/form.php';
require_once 'src/framework/markup.php';

class HTMLTwigExtension extends Twig_Extension
{
	public function getName()
	{
		return 'html';
	}

	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('html_nonce', [__CLASS__, 'nonce'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_email', [__CLASS__, 'email'], ['is_variadic' => true, 'is_safe' => ['html']]),
		];
	}

	public function getFilters()
	{
		return [
			new Twig_SimpleFilter('parse_markup', 'markup_parse', ['is_safe' => ['html']]),
			new Twig_SimpleFilter('strip_markup', 'markup_strip'),
			new Twig_SimpleFilter('excerpt', 'text_excerpt')
		];
	}

	static public function nonce($action, array $arguments = array())
	{
		$action_name = nonce_action_name($action, $arguments);

		return self::input_hidden('_nonce', nonce_generate($action_name));
	}

	static public function email($email, array $arguments = [])
	{
		return sprintf('<a href="mailto:%s">%s</a>',
			markup_format_attribute($email),
			markup_format_text($email));
	}
}