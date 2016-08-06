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
			new Twig_SimpleFilter('full_name', 'member_full_name'),
			new Twig_SimpleFilter('personal_full_name', function($member) {
				return member_full_name($member, BE_PERSONAL);
			}),
			new Twig_SimpleFilter('full_name_ignore_privacy', function($member) {
				return member_full_name($member, IGNORE_PRIVACY);
			}),
			new Twig_SimpleFilter('period_short', 'agenda_short_period_for_display'),
			new Twig_SimpleFilter('period', 'agenda_period_for_display')
		];
	}

	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('__', '__'),
			new Twig_SimpleFunction('_ngettext', '_ngettext'),
			new Twig_SimpleFunction('link_static', 'get_theme_data'),
			new Twig_SimpleFunction('get_config_value', 'get_config_value')
		];
	}
}