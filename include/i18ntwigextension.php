<?php
require_once 'include/member.php'; // for member_full_name

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
			new Twig_SimpleFilter('period', 'agenda_period_for_display'),
			new Twig_SimpleFilter('array_filter', 'array_filter')
		];
	}

	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('__', '__'),
			new Twig_SimpleFunction('__N', '__N'),
			new Twig_SimpleFunction('link_static', 'get_theme_data'),
			new Twig_SimpleFunction('get_config_value', 'get_config_value')
		];
	}

	public function getTests()
	{
		return [
			new Twig_SimpleTest('numeric', 'is_numeric'),
			new Twig_SimpleTest('instance_of', function($var, $classname) {
				return $var instanceof $classname; 
			})
		];
	}
}