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
			new Twig_SimpleFunction('html_input_datetime', [__CLASS__, 'input_datetime'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_checkbox', [__CLASS__, 'input_checkbox'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_radio', [__CLASS__, 'input_radio'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_hidden', [__CLASS__, 'input_hidden'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_input_submit', [__CLASS__, 'input_submit'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_select_field', [__CLASS__, 'select_field'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_textarea_field', [__CLASS__, 'textarea_field'], ['is_variadic' => true, 'is_safe' => ['html']]),
			new Twig_SimpleFunction('html_label', [__CLASS__, 'label'], ['is_variadic' => true, 'is_safe' => ['html']]),
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

		if (!isset($params['nopost']) && array_path($_POST, $field) !== null)
			$attributes['value'] = array_path($_POST, $field);
		elseif ($data && array_path($data, $field) !== null)
			$attributes['value'] = isset($params['formatter'])
				? call_user_func($params['formatter'], array_path($data, $field))
				: array_path($data, $field);

		if (
			(isset($params['errors']) && is_array($params['errors']) && in_array($name, $params['errors']))
			|| (isset($params['errors']) && isset($params['errors'][$name])))
			$params['class'] = (isset($params['class']) ? ($params['class'] . '_') : '') . 'error';
		
		unset($params['errors']);
		unset($params['nopost']);
		unset($params['formatter']);

		$attributes = array_merge($attributes, $params);
		
		$result = '<input';

		foreach ($attributes as $attribute => $value) {
			if (is_int($attribute))
				trigger_error('input_field tries to make an attribute without an attribute name', E_USER_WARNING);

			if ($value !== null && $value !== false)
				$result .= sprintf(' %s="%s"', str_replace('_', '-', $attribute), markup_format_attribute($value));
		}
		
		return $result . '>';
	}

	static public function input_text($name, $data, array $params = array())
	{
		if (!isset($params['type']))
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
		$params['type'] = 'date';
		$params['placeholder'] = sprintf(__('Bijv. %d-9-20'), date('Y'));

		if (!isset($params['class']))
			$params['class'] = 'date';
		
		return self::input_field($name, $data, $params);
	}

	static public function input_datetime($name, $data, array $params = array())
	{
		$params['type'] = 'datetime-local';
		$params['placeholder'] = sprintf(__('Bijv. %d-9-20 11:00'), date('Y'));
		$params['formatter'] = function($datetime) {
			return trim($datetime) != '' ? date('Y-m-d\TH:i', strtotime($datetime)) : '';
		};

		if (!isset($params['class']))
			$params['class'] = 'datetime';
		
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
		elseif (isset($_POST[$name]) && $_POST[$name] == $value) 
			$params['checked'] = 'checked';
		elseif (isset($data[$field])) {
			if ($value == 'on' || $value == 'yes') {// Boolean mode
				if ((bool) $data[$field])
					$params['checked'] = 'checked';
			} else {
				if ($data[$field] === $value)
					$params['checked'] = 'checked';
			}
		}

		$hidden_field = self::input_field($name, null, [
			'type' => 'hidden',
			'value' => '',
			'nopost' => true,
			'id' => null
		]);

		if (substr($field, -2, 2) == '[]')
			$hidden_field = '';

		$checkbox_field = self::input_field($name, null, $params);

		return $hidden_field . $checkbox_field;
	}

	static public function input_radio($name, $data, $value, array $params = array())
	{
		$params['type'] = 'radio';
		$params['value'] = $value;
		$params['nopost'] = true;

		if (!isset($params['id']))
			$params['id'] = sprintf('field-%s-%s', $name, $value);

		if (isset($params['field']))
			$field = $params['field'];
		else
			$field = $name;
		
		if (substr($field, -2, 2) == '[]') {
			if (isset($data[substr($field, 0, -2)]) && in_array($value, $data[substr($field, 0, -2)]))
				$params['checked'] = 'checked';
		}
		elseif (isset($_POST[$name]) && $_POST[$name] == $value) 
			$params['checked'] = 'checked';
		elseif (isset($data[$field])) {
			if ($value === 'on' || $value === 'yes') {// Boolean mode
				if ((bool) $data[$field])
					$params['checked'] = 'checked';
			} else {
				if ($data[$field] == $value)
					$params['checked'] = 'checked';
			}
		}
		
		return self::input_field($name, null, $params);
	}

	static public function input_hidden($name, $value, array $params = array())
	{
		$params['type'] = 'hidden';
		$params['nopost'] = true;
		$params['id'] = null; // Prevent an id from being set

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
		
		// Which value is selected
		if (!isset($params['nopost']) && get_post($name) !== null)
			$default = get_post($name);
		elseif (isset($data[$field]))
			$default = $data[$field];
		elseif (isset($params['default']))
			$default = $params['default'];
		else
			$default = null;
		unset($params['default']);

		// Do we need to add the error class?
		if (isset($params['errors']))
			if ((is_array($params['errors']) && in_array($field, $params['errors'])) || isset($params['errors'][$field]))
				if (isset($params['class']))
					$params['class'] .= ' error';
				else
					$params['class'] = 'error';
		unset($params['errors']);

		// Is the id overriden?
		if (!isset($params['id']))
			$params['id'] = 'field-' . $name;

		$options = '';

		if (!isset($params['required']) || !$params['required'])
			$options .= '<option value=""></option>';

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

		if (isset($params['errors']) && ((is_array($params['errors']) && in_array($name, $params['errors'])) || isset($params['errors'][$name]))) {
			if (isset($params['class']))
				$params['class'] = $params['class'] . '_error';
			else
				$params['class'] = 'error';
		}

		unset($params['errors']);

		$result = '<textarea name="' . markup_format_attribute($name) . '"';
		
		foreach ($params as $attribute => $val)
			if ($val !== null && $val !== false)
				$result .= ' ' . str_replace('_', '-', $attribute) . '="' . markup_format_attribute($val) . '"';
		
		return $result . ">\n" . $value . "</textarea>";
	}

	static public function label($name, $field, array $params = array())
	{
		$name = markup_format_text($name);
		$classes = isset($params['class']) ? explode(' ', $params['class']) : ['label'];
		$extra_content = '';
		
		if (isset($params['errors']) && ((is_array($params['errors']) && in_array($field, $params['errors'])) || isset($params['errors'][$field]))) {
			$params['class'] = (isset($params['class']) ? ($params['class'] . '_') : '') . 'error';
			$classes[] = 'label_error';
		}

		if (isset($params['required']) && $params['required']) {
			$classes[] = 'label_required';
			$extra_content = sprintf('<span class="required-badge" title="%s">*</span>', __('Verplicht'));
		}
		
		return sprintf('<label for="field-%s" class="%s">%s%s</label>',
			$field, implode(' ', $classes), $name, $extra_content);
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