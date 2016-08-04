<?php

class I18NTwigExtension extends Twig_Extension
{
	public function getName()
	{
		return 'i18n';
	}

	public function getFilters()
	{
		return [
			new Twig_SimpleFilter('trans', '__'),
			new Twig_SimpleFilter('translate_parts', '__translate_parts'),
			new Twig_SimpleFilter('ordinal', 'ordinal'),
			new Twig_SimpleFilter('member_full_name', 'member_full_name')
		];
	}

	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('__', '__'),
			new Twig_SimpleFunction('link_static', 'get_theme_data'),
			new Twig_SimpleFunction('get_config_value', 'get_config_value')
		];
	}
}