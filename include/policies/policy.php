<?php

interface Policy
{
	public function user_can_create(DataIter $iter);

	public function user_can_read(DataIter $iter);

	public function user_can_update(DataIter $iter);

	public function user_can_delete(DataIter $iter);
}

class PolicyNotFoundException extends Exception {
	//
}

function get_policy($model, $original_model_name = null)
{
	if (is_string($model)) {// Providing a name, e.g. DataIterFotoboek
		if (substr($model, 0, strlen('DataModel')) == 'DataModel')
			$model_name = substr($model, strlen('DataModel'));
		elseif (substr($model, 0, strlen('DataIter')) == 'DataIter')
			$model_name = substr($model, strlen('DataIter'));
		else
			throw new InvalidArgumentException('Cannot determine the policy for any class of which the name does not start with DataModel or DataIter.');
	}
	elseif (is_subclass_of($model, 'DataModel')) // 
		$model_name = substr(get_class($model), strlen('DataModel'));
	elseif (is_subclass_of($model, 'DataIter'))
		$model_name = substr(get_class($model), strlen('DataIter'));
	elseif ($model instanceof DataIter)
		return get_policy($model->model());
	elseif ($model === null)
		throw new InvalidArgumentException('get_policy() requires class name or instance of the model or the iter, null given.');
	else
		throw new InvalidArgumentException('Cannot determine model name from anonymous DataModel instance.');

	$policy = _get_cached_policy($model_name, $original_model_name);

	if ($policy !== null)
		return $policy;

	// But when the policy is not found, try the parent class
	if (($parent_class = get_parent_class($model)) !== false)
		if (is_subclass_of($parent_class, 'DataIter'))
			return get_policy($parent_class, $model_name);

	// If there is no parent class, or the parent class is not DataIter, then give up.
	throw new PolicyNotFoundException(sprintf("Policy for '%s' not found.", $model_name));
}

function _get_cached_policy($model_name, $original_model_name = null)
{
	static $policies = array();

	// Look in the policy cache
	if (isset($policies[$model_name]))
		return $policies[$model_name];

	// No policy in cache, construct the class name and load it
	$policy_class = 'Policy' . $model_name;

	$policy_file = stream_resolve_include_path('include/policies/' . $policy_class . '.php');

	// Policy class file does not exist? Then there is no policy for it.
	if ($policy_file === false)
		return $policies[$model_name] = null;

	require_once $policy_file;

	// Construct and cache the policy instance
	$policy = $policies[$model_name] = new $policy_class();

	// Also cache the policy under the original name for speedy lookup in the future
	if ($original_model_name !== null)
		$policies[$original_model_name] = $policy;

	return $policy;
}