<?php

interface Policy
{
	public function user_can_create();

	public function user_can_read(DataIter $iter);

	public function user_can_update(DataIter $iter);

	public function user_can_delete(DataIter $iter);
}

function get_policy($model)
{
	static $policies = array();

	$model_class = $model instanceof DataModel
		? get_class($model)
		: $model;

	$model_name = substr($model_class, strlen('DataModel'));

	assert('strlen($model_name) > 0');

	if (isset($policies[$model_name]))
		return $policies[$model_name];

	$policy_class = 'Policy' . $model_name;

	$policy_file = stream_resolve_include_path('include/policies/' . $policy_class . '.php');

	if ($policy_file === false)
		return $policies[$model_name] = null;

	require_once $policy_file;

	return $policies[$model_name] = new $policy_class();
}
