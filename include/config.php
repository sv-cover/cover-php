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
			include('config.inc');
		
		return $config;
	}
	
	/** @group Configuration
	  * Get a configuration value
	  * @key the configuration option to get
	  * @default the default value if the option can't be found
	  *
	  * @result the configuration value
	  */
	function get_config_value($key, $default = '') {
		$config = get_config();
		
		if (isset($config[$key]))
			return $config[$key];
		else
			return $default;
	}
?>
