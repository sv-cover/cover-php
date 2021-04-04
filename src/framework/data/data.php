<?php
	if (!defined('IN_SITE'))
		return;

	function create_model($name)
	{
		require_once 'src/models/' . $name . '.php';

		if (!class_exists($name))
			throw new InvalidArgumentException(sprintf(__("Could not find the model %s"), $name));

		$refl = new ReflectionClass($name);
		return $refl->newInstance(get_db());
	}

	/** @group Data
	  * Get a model. This function will create data models for you if 
	  * necessary. Mind that this function will only create one instance
	  * of a model and return that every time, unless specified otherwise.
	  * @param $name the name of the model
	  *
	  * @result a #DataModel object (either created or the one that was 
	  * created before), or false if the model could not be created
	  */
	function get_model(string $name)
	{
		static $models = array();

		return isset($models[$name])
			? $models[$name]
			: $models[$name] = create_model($name);
	}
	
	/** @group Data
	  * Get the database. The function will create a single instance
	  * of the database and return this every time
	  *
	  * @result the database instance
	  */
	function get_db() {
		static $db = null;
		
		if ($db == null)
		{
			require 'config/DBIds.php';

			if (!isset($dbids) || !is_array($dbids))
				throw new RuntimeException('Variable $dbids not defined as array in DBIds.php');

			$environment = get_static_config_value('environment', 'development');

			$database_class = isset($dbids[$environment]['class'])
				? $dbids[$environment]['class']
				: 'DatabasePDO';

			require_once 'src/framework/data/' . $database_class . '.php';

			if (!isset($dbids[$environment]))
				throw new RuntimeException("No database configuration for environment '$environment'");

			/* Create database */
			$db = new $database_class($dbids[$environment]);

			/* Enable query history if requested */
			if (get_static_config_value('show_queries', false))
				$db->track_history = true;
		}
		
		return $db;
	}
	
	/** @group Data
	  * Return a $_POST variable.
	  * @key the POST variable name to get the value of
	  * 
	  * @result the POST value or null if the key isn't in $_POST
	  */
	function get_post($key) {
		// Strip array field name thingies
		if (substr($key, -2) == '[]')
			$key = substr($key, 0, -2);

		if (!isset($_POST[$key]))
			return null;
		
		return $_POST[$key];
	}
