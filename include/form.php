<?php
	if (!defined('IN_SITE'))
		return;

	/** @group Form
	  * Function which parses a series of parameters in a single 
	  * dimensional array where a parameter key is followed by a
	  * parameter value (like param1, value1, param2, value2, ...)
	  * @args an array with parameters
	  * @num where the actual parameters start
	  *
	  * @result an associative array containing param => value pairs
	  * extracted from args starting at num
	  */
	function _parse_rest($args, $num) {
		if (count($args) <= $num)
			return Array();
		
		$result = Array();
		
		for ($i = $num; $i < count($args); $i += 2)
			$result[$args[$i]] = $args[$i + 1];
		
		return $result;
	}

	/** @group Form
	  * Function which creates a form input field. This function is 
	  * used by the other more convenient functions.
	  * For a full description of
	  * form element functions see #using_forms
	  * @name the name of the field
	  * @data the data of the form
	  * @params optional extra parameters like class, nopost, formatter
	  * , value and type (see #using_forms)
	  *
	  * @result a input field
	  */
	function _input_field($name, $data, $params) {
		if (isset($params['field'])) {
			$field = $params['field'];
			unset($params['field']);
		} else {
			$field = $name;
		}
		
		$attributes = Array('type' => $params['type']);

		if (!isset($params['id']))
			$attributes['id'] = 'field-' . $name;

		if ($name)
			$attributes['name'] = $name;

		if (!isset($params['nopost']) && ($value = get_post($name)) !== null)
			$attributes['value'] = str_replace('"', '\"', $value);
		elseif ($data && isset($data[$field]))
			$attributes['value'] = isset($params['formatter']) ? call_user_func($params['formatter'], $data[$field]) : $data[$field];

		if (isset($params['errors']) && in_array($name, $params['errors']))
			$params['class'] = (isset($params['class']) ? ($params['class'] . '_') : '') . 'error';
		
		unset($params['errors']);
		unset($params['nopost']);
		unset($params['formatter']);
		$attributes += $params;
		
		$result = '<input';

		foreach ($attributes as $attribute => $value)
			$result .= ' ' . $attribute . '="' . markup_format_attribute($value) . '"';
		
		return $result . '/>';
	}

	/** @group Form
	  * Convenient function to create a text field. 
	  * This function creates a text field. For a full description of
	  * form element functions see #using_forms
	  * @name the name of the field
	  * @data the form data
	  *
	  * @result a text field
	  */
	function input_text($name, $data) {
		$params = _parse_rest(func_get_args(), 2);
		$params['type'] = 'text';

		if (!isset($params['class']))
			$params['class'] = 'text';

		return _input_field($name, $data, $params);
	}

	/** @group Form
	  * Convenient function to create a password field. 
	  * This function creates a password field. For a full description of
	  * form element functions see #using_forms
	  * @name the name of the field
	  *
	  * @result a password field
	  */
	function input_password($name) {
		$params = _parse_rest(func_get_args(), 2);
		$params['type'] = 'password';
		$params['nopost'] = true;

		if (!isset($params['class']))
			$params['class'] = 'text';
		
		return _input_field($name, null, $params);
	}

	/** @group Form
	  * Convenient function to create a checkbox field. 
	  * This function creates a checkbox field (without a label). 
	  * For a full description of
	  * form element functions see #using_forms
	  * @name the name of the field
	  * @data the form data
	  * @value optional; the value of the field
	  *
	  * @result a checkbox field
	  */
	function input_checkbox($name, $data, $value = 'yes') {
		$params = _parse_rest(func_get_args(), 3);
		$params['type'] = 'checkbox';
		$params['value'] = $value;
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
		if (get_post($name) != null || $data[$field])
			$params['checked'] = 'checked';

		return _input_field($name, null, $params);
	}

	/** @group Form
	  * Convenient function to create a radio field. 
	  * This function creates a radio field (without a label). 
	  * For a full description of
	  * form element functions see #using_forms
	  * @name the name of the field
	  * @value the value of the field
	  *
	  * @result a radio field
	  */
	function input_radio($name, $data, $value) {
		$params = _parse_rest(func_get_args(), 3);
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

		return _input_field($name, null, $params);
	}

	/** @group Form
	  * Convenient function to create a hidden field. 
	  * This function creates a hidden field. For a full description of
	  * form element functions see #using_forms
	  * @name the name of the field
	  * @value the value of the field
	  *
	  * @result a hidden field
	  */
	function input_hidden($name, $value) {
		$params = _parse_rest(func_get_args(), 2);
		$params['type'] = 'hidden';
		
		if (!isset($params['nopost']))
			$params['nopost'] = true;

		return _input_field($name, array($name => $value), $params);
	}

	/** @group Form
	  * Convenient function to create a button field. 
	  * This function creates a button field. For a full description of
	  * form element functions see #using_forms
	  * @value the value of the button
	  * @onclick optional; the onclick action of the button
	  * @class optional; the button class
	  *
	  * @result a button
	  */
	function input_button($value, $onclick = null, $class = 'button') {
		$params = _parse_rest(func_get_args(), 3);

		$params['type'] = 'button';
		$params['nopost'] = true;
		$params['class'] = $class;
		$params['value'] = $value;
		
		if ($onclick)
			$params['onClick'] = $onclick;

		return _input_field($name, null, $params);
	}
	
	/** @group Form
	  * Convenient function to create an image field.
	  * This function creates an image field. For a full description of
	  * form element functions see #using_forms
	  * @src the image source path
	  *
	  * @result an image
	  */
	function input_image($src) {
		$params = _parse_rest(func_get_args(), 2);
		
		if (!isset($params['class']))
			$params['class'] = 'image';
		
		$params['type'] = 'image';
		$params['src'] = $src;
		
		return _input_field('', null, $params);
	}

	/** @group Form
	  * Convenient function to create a submit field. 
	  * This function creates a submit field. For a full description of
	  * form element functions see #using_forms
	  * @name the name of the field
	  * @value the value of the submit button
	  * @class optional; the button class
	  *
	  * @result a submit button
	  */
	function input_submit($name, $value, $class = 'button') {
		$params = _parse_rest(func_get_args(), 3);
		$params['type'] = 'submit';
		$params['nopost'] = true;
		$params['class'] = $class;
		$params['value'] = $value;

		return _input_field($name, null, $params);
	}

	/** @group Form
	  * Convenient function to create a combobox select field. 
	  * This function creates a select field and fills it with values.
	  * Either POST[name], data[name] or the parameter 'value' 
	  * (in that order) is selected by default. For a full description of
	  * form element functions see #using_forms
	  * @name the name of the field
	  * @values an array of value => title values
	  * @data an associative array with the data used in the form. 
	  *
	  * @result a select field
	  */
	function select_field($name, $values, $data) {
		$params = _parse_rest(func_get_args(), 3);

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
			$result .= ' ' . $attribute . '="' . markup_format_attribute($value) . '"';
		
		return $result . ">\n" . $options . "</select>";
	}
	
	function _textarea_format_default($value) {
		return markup_format_text($value);
	}
	
	/** @group Form
	  * Convenient function to create a textarea. This function creates
	  * a textarea and fills it with either POST[name], data[name] or
	  * the parameter 'value' (in that order). For a full description of
	  * form element functions see #using_forms
	  * @name the name of this textarea
	  * @data an associative array with the data used in the form. 
	  *
	  * @result a textarea
	  */
	function textarea_field($name, $data, $errors = null) {
		$params = _parse_rest(func_get_args(), 3);
		
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
			$value = _textarea_format_default($value);
		
		unset($params['value']);
		unset($params['formatter']);

		if ($errors && in_array($name, $errors)) {
			if (isset($params['class']))
				$params['class'] = $params['class'] . '_error';
			else
				$params['class'] = 'error';
		}

		if (!isset($params['cols']))
			$params['cols'] = '70';
		if (!isset($params['rows']))
			$params['rows'] = '10';

		$result = '<textarea name="' . $name . '"';
		
		foreach ($params as $attribute => $val)
			$result .= ' ' . $attribute . '="' . markup_format_attribute($val) . '"';
		
		return $result . ">\n" . $value . "</textarea>";
	}

	/** @group Form
	  * Create a label with error checking
	  * @name the name of the value this is a label for
	  * @field optional; the field name to use for this label
	  * @errors optional; an array with names of fields that have errors
	  * @required optional; whether the field with this label is required
	  *
	  * @result a label
	  */
	function label($name, $field = null, $errors = null, $required = false) {
		if ($field == null) {
			$field = $name;
			$name = ucfirst($name);
		}

		$name = markup_format_text($name);
		$classes = array('label');
				
		if ($errors && in_array($field, $errors))
			$classes[] = 'label_error';

		if ($required === true)
			$classes[] = 'label_required';
		
		return sprintf('<label for="field-%s" class="%s">%s</label>',
			$field, implode(' ', $classes), $name);
	}

	/** @group Form
	  * Convenient function to create a simple table row. All passed
	  * parameters are inserted as separate cells
	  *
	  * @result a string representing a table row
	  */
	function table_row() {
		$row = "<tr>\n";
		
		foreach (func_get_args() as $arg)
			$row .= "<td>$arg</td>\n";
		
		return $row . "</tr>\n";
	}

	/** @group Form
	  * A check function which checks if a value is a non empty
	  * @name the name of the POST value
	  * @value reference; the value
	  *
	  * @result true when value is non empty, false otherwise
	  */	
	function check_value_empty($name, $value) {
		if (!isset($value) || !trim($value))
			return false;
		else
			return $value;
	}

	/** @group Form
	  * A check function which checks if a value is a valid number. Besides
	  * checking this function will also convert the value to a float
	  * @name the name of the POST value
	  * @value reference; the value
	  *
	  * @result true when value is a number, false otherwise
	  */
	function check_value_tofloat($name, $value) {
		if (!is_numeric($value))
			return false;
		else
			return floatval($value);
	}
	
	/** @group Form
	  * A check function which checks if a value is a valid number. Besides
	  * checking this function will also convert the value to an int
	  * @name the name of the POST value
	  * @value reference; the value
	  *
	  * @result true when value is a number, false otherwise
	  */
	function check_value_toint($name, $value) {
		if (!is_numeric($value))
			return false;
		else
			return intval($value);
	}
	
	/** @group Form
	  * A check function which formats a checkbox value. It sets the
	  * POST value to 1 if the value is either 'yes' or 'on' and to 0
	  * otherwise
	  * @name the name of the POST value
	  * @value reference; the value
	  *
	  * @result always true
	  */
	function check_value_checkbox($name, $value) {
		if ($value == 'yes' || $value == 'on')
			return 1;
		else
			return 0;
	}
	
	/** @group Form
	  * A function which checks if POSTed values are valid and optionally
	  * formats values
	  *
	  * @check an array of values to check. Each item in this array
	  * is either a a string (the name of a field) or an associative array 
	  * containing a 'name' key (the name of a field) and a 'function' 
	  * key containing the check function to call. If only a name is
	  * specified the default check function (#check_value_empty) will
	  * be used. Check functions have two parameters: name and value and 
	  * returns either false on error or true when the value is valid. 
	  * The value parameter is passed by reference to allow formatting
	  * the value. Common check functions available are: 
	  * #check_value_toint and #check_value_checkbox
	  * @errors reference; will be set to an array of fields that didn't
	  * successfully check
	  *
	  * @result an array with name => value values
	  */
	function check_values($check, &$errors) {
		$fields = array();
		$errors = array();

		foreach ($check as $item) {
			if (!is_array($item)) {
				$name = $item;
				$func = 'check_value_empty';
			} else {
				$name = $item['name'];

				if (isset($item['function']))
					$func = $item['function'];
				else
					$func = 'check_value_empty';
			}
			
			$result = call_user_func($func, $name, get_post($name));
			
			if ($result === false)
				$errors[] = $name;
			else
				$fields[$name] = $result;
		}
		
		return $fields;
	}
?>
