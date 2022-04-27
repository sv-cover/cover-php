<?php
	if (!defined('IN_SITE'))
		return;

	/** @group Configuration
	  * Get the configuration hash
	  *
	  * @result a hash containing the configuration
	  */
	function get_config() {
		static $config = null;
		
		if ($config == null)
			include('config/config.inc');
		
		return $config;
	}
	
	/** @group Configuration
	  * Get a configuration value
	  * @key the configuration option to get
	  * @default the default value if the option can't be found
	  *
	  * @result the configuration value
	  */
	function get_config_value($key, $default = null)
	{
		$not_found = new stdClass();

		// First try dynamic config
		try {
			$value = get_dynamic_config_value($key, $not_found);
		} catch (LogicException $e) {
			trigger_error($e, E_USER_WARNING);
			$value = $not_found;
		}

		// Then try static config
		if ($value === $not_found)
			$value = get_static_config_value($key, $not_found);

		// and if all that fails, return default value
		if ($value === $not_found)
			$value = $default;

		return $value;
	}


	function get_static_config_value($key, $default = null)
	{
		$config = get_config();
		return isset($config[$key])
			? $config[$key]
			: $default;
	}

	function get_dynamic_config_value($key, $default = null)
	{
		static $in_call;

		if ($in_call)
			throw new LogicException('Calling get_dynamic_config_value while calling get_dynamic_config_value. Recursion!');

		$in_call = true;

		$model = get_model('DataModelConfiguratie');
		$value = $model->get_value($key, $default);

		$in_call = false;

		return $value;
	}
?>
