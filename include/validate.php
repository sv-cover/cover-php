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

function validate_dataiter(DataIter $iter, array &$data, array &$errors)
{
	$rules = $iter->rules();

	foreach ($rules as $field => $options)
	{
		$cleaner = isset($options['clean']) ? $options['clean'] : 'trim';

		$validators = isset($options['validate']) ? $options['validate'] : [];

		$required = isset($options['required']) ? $options['required'] : false;

		if (!isset($data[$field])) {
			if (!$iter->has_id() && $required)
				$errors[] = $field;

			continue;
		}

		$data[$field] = call_user_func($cleaner, $data[$field]);

		foreach ($validators as $validator)
		{
			if (is_string($validator))
				$validator = 'validate_' . $validator;

			if (!call_user_func($validator, $data[$field], $field, $iter)) {
				$errors[] = $field;
				break;
			}
		}
	}

	return count($errors) === 0;
}