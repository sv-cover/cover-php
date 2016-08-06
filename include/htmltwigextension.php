<?php
require_once 'include/form.php';

class HTMLTwigExtension extends Twig_Extension
{
	public function getName()
	{
		return 'html';
	}

	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('html_input_text', [__CLASS__, 'input_text'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_password', [__CLASS__, 'input_password'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_date', [__CLASS__, 'input_date'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_checkbox', [__CLASS__, 'input_checkbox'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_radio', [__CLASS__, 'input_radio'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_hidden', [__CLASS__, 'input_hidden'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_submit', [__CLASS__, 'input_submit'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_select_field', [__CLASS__, 'select_field'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_textarea_field', [__CLASS__, 'textarea_field'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_label', [__CLASS__, 'label'], ['is_variadic' => true, 'is_safe' => ['html']])
		];
	}

	public function getFilters()
	{
		return [
			new Twig_SimpleFilter('parse_markup', 'markup_parse', ['is_safe' => ['html']]),
			new Twig_SimpleFilter('filter', function($array, $callback) {
				return array_map($callback, $array);
			})
		];
	}

	static protected function input_field($name, $data, $params)
	{
		if (isset($params['field'])) {
			$field = $params['field'];
			unset($params['field']);
		} else {
			$field = $name;
		}
		
		$attributes = array('type' => $params['type']);

		if (!isset($params['id']))
			$attributes['id'] = 'field-' . $name;

		if ($name)
			$attributes['name'] = $name;

		if (!isset($params['nopost']) && isset($_POST[$name]))
			$attributes['value'] = $_POST[$name];
		elseif ($data && isset($data[$field]))
			$attributes['value'] = isset($params['formatter']) ? call_user_func($params['formatter'], $data[$field]) : $data[$field];

		if (isset($params['errors']) && in_array($name, $params['errors']))
			$params['class'] = (isset($params['class']) ? ($params['class'] . '_') : '') . 'error';
		
		unset($params['errors']);
		unset($params['nopost']);
		unset($params['formatter']);

		$attributes = array_merge($attributes, $params);
		
		$result = '<input';

		foreach ($attributes as $attribute => $value)
			$result .= ' ' . str_replace('_', '-', $attribute) . '="' . markup_format_attribute($value) . '"';
		
		return $result . '>';
	}

	static public function input_text($name, $data, array $params = array())
	{
		$params['type'] = 'text';

		if (!isset($params['class']))
			$params['class'] = 'text';

		return self::input_field($name, $data, $params);
	}

	static public function input_password($name, array $params = array())
	{
		$params['type'] = 'password';
		$params['nopost'] = true;

		if (!isset($params['class']))
			$params['class'] = 'text';
		
		return self::input_field($name, null, $params);
	}

	static public function input_date($name, $data, array $params = array())
	{
		$params = _parse_rest(func_get_args(), 2);
		$params['type'] = 'date';
		$params['placeholder'] = sprintf(__('E.g. %d-12-31'), date('Y'));

		if (!isset($params['class']))
			$params['class'] = 'date';
		
		return self::input_field($name, $data, $params);
	}

	static public function input_checkbox($name, $data, array $params = array())
	{
		$value = isset($params['value']) ? $params['value'] : 'yes';

		$params['type'] = 'checkbox';
		$params['value'] = (string) $value;
		$params['nopost'] = true;

		if (isset($params['field']))
			$field = $params['field'];
		else
			$field = $name;
		
		/* TODO: this doesn't work well when $data[$field] is set and
		 * the form has been posted before (contained errors) and this
		 * checkbox was unchecked (since then get_post($name) will be
		 * null again (I think) and $data[$field] takes over
		 */
		if (substr($field, -2, 2) == '[]') {
			if (isset($data[substr($field, 0, -2)]) && in_array($value, $data[substr($field, 0, -2)]))
				$params['checked'] = 'checked';
		}
		elseif (isset($_POST[$name]) && $_POST[$name] == $value || isset($data[$field]) && $data[$field] == $value)
			$params['checked'] = 'checked';

		return self::input_field($name, null, $params);
	}

	static public function input_radio($name, $data, $value, array $params = array())
	{
		$params['type'] = 'radio';
		$params['value'] = $value;
		$params['nopost'] = true;

		if (isset($params['field']))
			$field = $params['field'];
		else
			$field = $name;
		
		if (get_post($name) != null) {
			if (get_post($name) == $value)
				$params['checked'] = 'checked';
		} elseif ($data[$field] == $value) {
			$params['checked'] = 'checked';
		}

		return self::input_field($name, null, $params);
	}

	static public function input_hidden($name, $value, array $params = array())
	{
		$params['type'] = 'hidden';
		$params['nopost'] = true;

		return self::input_field($name, array($name => $value), $params);
	}

	static public function input_submit($name, $value, array $params = array())
	{
		$params['type'] = 'submit';
		$params['nopost'] = true;
		
		if (!isset($params['class']))
			$params['class'] = 'button';
		
		$params['value'] = $value;

		return self::input_field($name, null, $params);
	}

	static function select_field($name, $values, $data, array $params = array())
	{
		if (isset($params['field'])) {
			$field = $params['field'];
			unset($params['field']);
		} else {
			$field = $name;
		}
	
		if (!isset($params['nopost']) && get_post($name) !== null)
			$default = get_post($name);
		elseif (isset($data[$field]))
			$default = $data[$field];
		elseif (isset($params['default']))
			$default = $params['default'];
		else
			$default = null;
		
		unset($params['default']);
		$options = '';

		if (!isset($params['id']))
			$params['id'] = 'field-' . $name;

		foreach ($values as $val => $title)
			$options .= '<option value="' . markup_format_attribute($val) . '"' . (($default !== null && $val == $default) ? ' selected="selected"' : '') . '>' . markup_format_text($title) . "</option>\n";
		
		$result = '<select name="' . $name . '"';
		
		foreach ($params as $attribute => $value)
			$result .= ' ' . str_replace('_', '-', $attribute) . '="' . markup_format_attribute($value) . '"';
		
		return $result . ">\n" . $options . "</select>";
	}

	static public function textarea_field($name, $data, array $params = array())
	{
		if (isset($params['field'])) {
			$field = $params['field'];
			unset($params['field']);
		} else {
			$field = $name;
		}

		if (!isset($params['nopost']) && isset($_POST[$name]))
			$value = get_post($name);
		elseif (isset($data[$field]))
			$value = $data[$field];
		elseif (isset($params['value']))
			$value = $params['value'];
		else
			$value = '';
		
		if (isset($params['formatter']))
			$value = call_user_func($params['formatter'], $value);
		else
			$value = markup_format_text($value);
		
		unset($params['value']);
		unset($params['formatter']);

		if (isset($params['errors']) && in_array($name, $params['errors'])) {
			if (isset($params['class']))
				$params['class'] = $params['class'] . '_error';
			else
				$params['class'] = 'error';
		}

		unset($params['errors']);

		$result = '<textarea name="' . markup_format_attribute($name) . '"';
		
		foreach ($params as $attribute => $val)
			$result .= ' ' . str_replace('_', '-', $attribute) . '="' . markup_format_attribute($val) . '"';
		
		return $result . ">\n" . $value . "</textarea>";
	}

	static public function label($name, $field, array $params = array())
	{
		$name = markup_format_text($name);
		$classes = isset($params['class']) ? explode(' ', $params['class']) : ['label'];
				
		if (isset($params['errors']) && in_array($field, $params['errors']))
			$classes[] = 'label_error';

		if (isset($params['required']) && $params['required'])
			$classes[] = 'label_required';
		
		return sprintf('<label for="field-%s" class="%s">%s</label>',
			$field, implode(' ', $classes), $name);
	}
}