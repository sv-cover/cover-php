<?php

class RouterTwigExtension extends Twig_Extension
{
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
		return '#' . $name;
	}
}