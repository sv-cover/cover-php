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
			new Twig_SimpleFilter('full_name', function($member) {
				return $member ? member_full_name($member) : null;
			}),
			new Twig_SimpleFilter('personal_full_name', function($member) {
				return $member ? member_full_name($member, BE_PERSONAL) : null;
			}),
			new Twig_SimpleFilter('full_name_ignore_privacy', function($member) {
				return $member ? member_full_name($member, IGNORE_PRIVACY) : null;
			}),
			new Twig_SimpleFilter('first_name', 'member_first_name'),
			new Twig_SimpleFilter('period_short', 'agenda_short_period_for_display', ['is_safe' => ['html']]),
			new Twig_SimpleFilter('period', 'agenda_period_for_display', ['is_safe' => ['html']]),
			new Twig_SimpleFilter('date_relative', 'format_date_relative'),
			new Twig_SimpleFilter('vformat', 'vsprintf'),
			new Twig_SimpleFilter('map', function($iterable, $callback) {
				return array_map($callback, $iterable);
			}),
			new Twig_SimpleFilter('map_macro', function($context, $iterable, $callback) {
				list($macro_context, $macro_name) = explode('.', $callback);
				return array_map([$context[$macro_context], 'get' . $macro_name], $iterable);
			}, ['needs_context' => true]),
			new Twig_SimpleFilter('human_join', 'implode_human'),
			new Twig_SimpleFilter('human_file_size', 'human_file_size'),
			new Twig_SimpleFilter('flip', 'array_flip'),
			new Twig_SimpleFilter('values', 'array_values'),
			new Twig_SimpleFilter('select', 'array_select'),
			new Twig_SimpleFilter('sum', 'array_sum'),
			new Twig_SimpleFilter('group_by', function($iters, $property) {
				$groups = [];

				foreach ($iters as $iter)
					if (!isset($groups[$iter[$property]]))
						$groups[$iter[$property]] = [$iter];
					else
						$groups[$iter[$property]][] = $iter;

				return $groups;
			}),
			new Twig_SimpleFilter('sort_by', function($iters, ...$args) {
				$sort_args = [];

				foreach ($args as $sort_arg) {
					if (!preg_match('/^(?P<index>[^\s]+)(?:\s+(?P<order>asc|desc))$/i', $sort_arg, $match))
						throw new InvalidArgumentException('Cannot parse sort arg: '. $sort_arg);

					$sort_args[] = array_select($iters, $match['index']);
					switch ($match['order']) {
						case 'desc':
							$sort_args[] = SORT_DESC;
							break;
						case 'asc':
						default:
							$sort_args[] = SORT_ASC;
							break;
					}
				}

				$sort_args[] =& $iters;

				array_multisort(...$sort_args);

				return $iters;
			})
		];
	}

	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('__', '__'),
			new Twig_SimpleFunction('__N', function($singular, $plural, $value, $count = null) {
				if ($count === null) $count = $value;
				return sprintf(_ngettext($singular, $plural, $count), $value);
			}, ['variadic' => true]),
			new Twig_SimpleFunction('__translate_parts', '__translate_parts'),
			new Twig_SimpleFunction('link_static', 'get_theme_data'),
			new Twig_SimpleFunction('get_config_value', 'get_config_value'),
			new Twig_SimpleFunction('var_dump', function($value) {
				ob_start();
				var_dump($value);
				return '<pre style="text-align: left">' . ob_get_clean() . '</pre>';
			}, ['is_safe' => ['html']])
		];
	}

	public function getTests()
	{
		return [
			new Twig_SimpleTest('numeric', 'is_numeric'),
			new Twig_SimpleTest('instance_of', function($var, $classname) {
				return $var instanceof $classname; 
			}),
			new Twig_SimpleTest('past', function($date) {
				if (!$date)
					return false;
				
				if (!($date instanceof DateTime))
					$date = new DateTime($date);

				return $date < new DateTime();
			}),
			new Twig_SimpleTest('future', function($date) {
				if (!$date)
					return false;
				
				if (!($date instanceof DateTime))
					$date = new DateTime($date);

				return $date > new DateTime();
			})
		];
	}
}