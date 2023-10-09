<?php

class StopValidation extends Exception
{
	//
}

function validate_not_empty($value)
{
	return strlen($value) > 0;
}

function validate_email($value)
{
	return filter_var($value, FILTER_VALIDATE_EMAIL);
}

function validate_committee($committee_id)
{
	try {
		get_model('DataModelCommissie')->get_iter($committee_id);
		return true;
	} catch (NotFoundException $e) {
		return false;
	}
}

function validate_member($member_id)
{
	try {
		get_model('DataModelMember')->get_iter($member_id);
		return true;
	} catch (NotFoundException $e) {
		return false;
	}
}

function validate_datetime($value) 
{
	if ($value instanceof DateTime)
		return $value;
	
	try {
		return new DateTime($value);
	} catch (Exception $e) {
		return false;
	}
}

function validate_filemanger_file($value)
{
	// Max length == 255
	if (strlen($value) > 255)
		return false;

	// Only accept image file (using naive extension check)
	$ext = pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION);
	$allowed_exts = get_config_value('filemanager_image_extensions', ['jpg', 'jpeg', 'png']);
	return in_array(strtolower($ext), $allowed_exts);
}

/**
 * A validator for optional fields.
 * Why? The valication chain will be executed on empty values. This validator
 * allows to stop the validation chain on empty values, so that non-empty 
 * values can be validated. This implementation is analogous to the WTForms
 * Python library.
 */
function validate_optional($value)
{
	if (empty($value))
		throw new StopValidation();
	return true;
}

function trim_string($value)
{
	return is_string($value) ? trim($value) : $value;
}

function clean_empty($value)
{
	$value = trim_string($value);

	return empty($value) ? null : $value;
}

function clean_checkbox($value) {
	if ($value == 'yes' || $value == 'on')
		return 1;
	else
		return 0;
}

function validate_dataiter(DataIter $iter, array $data, &$errors)
{
	$rules = $iter->rules();

	$out = [];

	// First fill $out, then validate it, so that the validation functions
	// have access to all submitted (and maybe invalid) data.
	foreach ($rules as $field => $options)
	{
		$required = $options['required'] ?? false;

		$is_checkbox = $options['is_checkbox'] ?? false;

		$cleaner = $options['clean'] ?? ($is_checkbox ? 'clean_checkbox' : 'trim_string');

		if (!isset($data[$field])) {
			if (!$iter->has_id() && $required) {
				$errors[] = $field;
				continue;
			}

			if (!$iter->has_id() && isset($options['default']))
				$data[$field] = call_user_func($options['default'], $field, $iter);
			elseif ($is_checkbox)
				// None required checkbox has a default of false, unless set otherwise
				$data[$field] = false;
			else
				continue;
		}

		$out[$field] = call_user_func($cleaner, $data[$field]);
	}

	foreach ($rules as $field => $options)
	{
		// Only check fields that are submitted. Requirement checks have been done in the previous loop.

		if (!isset($out[$field]))
			continue;

		$validators = $options['validate'] ?? [];

		foreach ($validators as $validator)
		{
			if (is_string($validator))
				$validator = 'validate_' . $validator;

			try {
				if (!call_user_func($validator, $out[$field], $field, $iter, $out)) {
					$errors[] = $field;
					break;
				}
			} catch (StopValidation $e) {
				break;
			}
		}
	}

	return count($errors) === 0 ? $out : false;
}
