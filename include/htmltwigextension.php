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
			new Twig_SimpleFunction('html_input_number', [__CLASS__, 'input_number'], ['is_variadic' => true, 'is_safe' => ['html']]),
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

	static protected function input_attributes(array $attributes)
	{
		$result = '';

		foreach ($attributes as $attribute => $value) {
			if (is_int($attribute))
				trigger_error('input_field tries to make an attribute without an attribute name', E_USER_WARNING);

			if ($value !== null && $value !== false)
				$result .= sprintf(' %s="%s"', str_replace('_', '-', $attribute), markup_format_attribute($value));
		}
		
		return $result;
	}

	static protected function _input_field($name, $data, $params)
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
			$params['class'] = (isset($params['class']) ? ($params['class'] . ' ') : '') . 'is-danger';
		
		unset($params['errors']);
		unset($params['nopost']);
		unset($params['formatter']);

		$attributes = array_merge($attributes, $params);
		
		return sprintf('<input%s>', self::input_attributes($attributes));
	}

	static protected function input_field($name, $data, $params)
	{
		$params['class'] = (isset($params['class']) ? ($params['class'] . ' ') : '') . 'input';
		return self::_input_field($name, $data, $params);
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

	static public function input_number($name, $data, array $params = array())
	{
		$params['type'] = 'number';

		if (!isset($params['class']))
			$params['class'] = 'number';
		
		return self::input_field($name, $data, $params);
	}

	static public function input_date($name, $data, array $params = array())
	{
		$params['type'] = 'date';

		if (!isset($params['placeholder']))
			$params['placeholder'] = sprintf(__('E.g. %d-9-20'), date('Y'));

		if (!isset($params['class']))
			$params['class'] = 'date';
		
		return self::input_field($name, $data, $params);
	}

	static public function input_datetime($name, $data, array $params = array())
	{
		$params['type'] = 'datetime-local';
		$params['formatter'] = function($datetime) {
			return trim($datetime) != '' ? date('Y-m-d\TH:i', strtotime($datetime)) : '';
		};
		
		if (!isset($params['placeholder']))
			$params['placeholder'] = sprintf(__('E.g. %d-9-20 11:00'), date('Y'));

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
		
		if (isset($params['label'])){
			$label = $params['label'];
			unset($params['label']);
		}
		else
			$label = false;

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

		$checkbox_field = self::_input_field($name, null, $params);

		if ($label) {
			$label = markup_format_text($label);
			$params['class'] = (isset($params['class']) ? ($params['class'] . ' ') : '') . 'checkbox';
			$id = isset($params['id']) ? preg_replace('/^field-/', '', $params['id'], 1) : $name;
			$checkbox_field = self::_label(sprintf('%s %s', $checkbox_field, $label), $id, $params);
		}

		if (!empty($params['add_hidden']))
			$hidden_field = self::_input_field($name, null, [
				'type' => 'hidden',
				'value' => '',
				'nopost' => true,
				'id' => null
			]);
		else
			$hidden_field = '';

		return $hidden_field . $checkbox_field;
	}

	static public function input_radio($name, $data, $value, array $params = array())
	{
		$params['type'] = 'radio';
		$params['value'] = $value;
		$params['nopost'] = true;
		
		if (isset($params['label'])){
			$label = $params['label'];
			unset($params['label']);
		}
		else
			$label = false;

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
		
		$radio_field = self::_input_field($name, null, $params);
		if ($label) {
			$label = markup_format_text($label);
			$params['class'] = (isset($params['class']) ? ($params['class'] . ' ') : '') . 'radio';
			$id = isset($params['id']) ? preg_replace('/^field-/', '', $params['id'], 1) : $name;
			$radio_field = self::_label(sprintf('%s %s', $radio_field, $label), $id, $params);
		}

		return $radio_field;
	}

	static public function input_hidden($name, $value, array $params = array())
	{
		$params['type'] = 'hidden';
		$params['nopost'] = true;
		$params['id'] = null; // Prevent an id from being set

		return self::_input_field($name, array($name => $value), $params);
	}

	static public function input_submit($name, $value, array $params = array())
	{
		$params['type'] = 'submit';
		$params['nopost'] = true;
		
		if (!isset($params['class']))
			$params['class'] = 'button';
		
		$params['value'] = $value;

		return self::_input_field($name, null, $params);
	}

	static function select_field($name, $values, $data, array $params = array())
	{
		if (isset($params['field'])) {
			$field = $params['field'];
			unset($params['field']);
		} else {
			$field = $name;
		}

		$params['name'] = $name;
		
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

		$html = [];

		if (!isset($params['required']) || !$params['required'])
			$html[] = '<option value=""></option>';

		foreach ($values as $group => $options)
		{
			if (!is_array($options)) {
				$options = [$group => $options];
				$group = null;
			}

			if (count($options) === 0)
				continue;

			$html_options = [];

			foreach ($options as $val => $title)
			{
				if ($default === null)
					$is_selected = false;
				elseif (is_array($default))
					$is_selected = in_array($val, $default);
				else
					$is_selected = $val == $default;

				$html_options[] = sprintf('<option value="%s"%s>%s</option>',
					markup_format_attribute($val),
					$is_selected ? ' selected' : '',
					markup_format_text($title));
			}

			$html[] = $group
				? sprintf('<optgroup label="%s">%s</optgroup>', markup_format_attribute($group), implode("\n", $html_options))
				: implode("\n", $html_options);
		}
		
		return sprintf('<select%s>%s</select>', self::input_attributes($params), implode("\n", $html));
	}

	static public function textarea_field($name, $data, array $params = array())
	{
		$params['class'] = (isset($params['class']) ? ($params['class'] . ' ') : '') . 'textarea';

		if (isset($params['field'])) {
			$field = $params['field'];
			unset($params['field']);
		} else {
			$field = $name;
		}

		if (!isset($params['id']))
			$params['id'] = 'field-' . $name;

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

	static public function _label($name, $field, array $params = array())
	{
		$classes = isset($params['class']) ? explode(' ', $params['class']) : ['label'];
		$extra_content = '';
		$for = isset($params['for']) ? $params['for'] : sprintf('field-%s', $field);
		
		if (isset($params['errors']) && ((is_array($params['errors']) && in_array($field, $params['errors'])) || isset($params['errors'][$field]))) {
			$params['class'] = (isset($params['class']) ? ($params['class'] . '_') : '') . 'error';
			$classes[] = 'label_error';
		}

		if (isset($params['required']) && $params['required']) {
			$classes[] = 'label_required';
			$extra_content = sprintf('<span class="required-badge" title="%s">*</span>', __('Required'));
		}
		
		return sprintf('<label for="%s" class="%s">%s%s</label>',
			$for, implode(' ', $classes), $name, $extra_content);
	}

	static public function label($name, $field, array $params = array())
	{
		$name = markup_format_text($name);
		return self::_label($name, $field, $params);
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