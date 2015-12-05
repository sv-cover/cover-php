<?php
	if (!defined('IN_SITE'))
		return;

	/** @group Data
	  * Get a model. This function will create data models for you if 
	  * necessary. Mind that this function will only create one instance
	  * of a model and return that every time.
	  * @name the name of the model (this can be either the name of a class, 
	  * in which case the a new object of that class is created, or a function
	  * name which is expanded to model_create_[name]
	  *
	  * @result a #DataModel object (either created or the one that was 
	  * created before), or false if the model could not be created
	  */
	function get_model($name) {
		static $models = Array();
		
		if (isset($models[$name]))
			return $models[$name];
		
		if (function_exists("model_create_$name"))
			$models[$name] = call_user_func("model_create_$name");
		else {
			require_once 'include/models/' . $name . '.php';

			if (class_exists($name)) {
				$models[$name] = new $name(get_db());
			} else {
				report_error('Data', N__("Kan het model %s niet vinden"), $name);
				$models[$name] = false;
			}
		}
		
		return $models[$name];		
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
			require 'include/data/DBIds.php';

			$database_class = isset($dbids['easy']['class'])
				? $dbids['easy']['class']
				: 'DatabasePDO';

			require_once 'include/data/' . $database_class . '.php';

			/* Create database */
			$db = new $database_class($dbids['easy']);

			/* Enable query history if requested */
			if (get_config_value('show_queries', false))
				$db->history = array();
		}
		
		return $db;
	}
	
	/** @group Data
	  * Return a $_POST variable. This function will stripslashes when
	  * get_magic_quotes_gpc is true so that $_POST values are unified
	  * regardless of the PHP setup.
	  * @key the POST variable name to get the value of
	  * 
	  * @result the POST value or null if the key isn't in $_POST
	  */
	function get_post($key) {
		if (!isset($_POST[$key]))
			return null;
		
		if (get_magic_quotes_gpc())
			return stripslashes($_POST[$key]);
		else
			return $_POST[$key];
	}
?>
