<?php

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

function trim_string($value)
{
	return is_string($value) ? trim($value) : $value;
}

function validate_dataiter(DataIter $iter, array $data, &$errors)
{
	$rules = $iter->rules();

	$out = [];

	// First fill $out, then validate it, so that the validation functions
	// have access to all submitted (and maybe invalid) data.
	foreach ($rules as $field => $options)
	{
		$cleaner = isset($options['clean']) ? $options['clean'] : 'trim_string';

		$required = isset($options['required']) ? $options['required'] : false;

		if (!isset($data[$field])) {
			if (!$iter->has_id() && $required) {
				$errors[] = $field;
				continue;
			}

			if ($iter->has_id())
				continue;

			if (isset($options['default']))
				$data[$field] = call_user_func($options['default'], $field, $iter);
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

		$validators = isset($options['validate']) ? $options['validate'] : [];

		foreach ($validators as $validator)
		{
			if (is_string($validator))
				$validator = 'validate_' . $validator;

			if (!call_user_func($validator, $out[$field], $field, $iter, $out)) {
				$errors[] = $field;
				break;
			}
		}
	}

	return count($errors) === 0 ? $out : false;
}