<?php
	if (!defined('IN_SITE'))
		return;

	require_once 'include/config.php';
	require_once 'include/exception.php';
	require_once 'include/data.php';
	require_once 'include/view.php';

	function _dump($arg) {
		printf('<code style="overflow-x:scroll;display:block;background:white;padding:10px;border:1px solid black;"><pre>%s</pre></code>', $arg);
		return $arg;
	}

	function _get_backtrace() {
		$skip = func_get_args();
		$skip[] = '_get_backtrace';
		$result = '';

		foreach (debug_backtrace() as $trace) {
			if (!in_array($trace['function'], $skip)) {
				$result .= $trace['file'] . "\n";
				$result .= ($trace['class'] ? ($trace['class'] . '::') : '');
				
				$args = '';
				
				foreach ($trace['args'] as $arg) {
					if ($args != '')
						$args .= ', ';
					
					$args .= @var_export($arg, true);
				}
				
				$result .= $trace['function'] . ' (' . $args . ')';
				$result .= ', line ' . $trace['line'];
			}
		}
		
		return $result;
	}

	/** @group Functions
	  * Report an error to the user and optionally email the error 
	  * depending on the configuration option 'report_error'
	  * @group the group the error is in
	  * @description the description of the error
	  */
	function report_error($group, $descformat) {
		$formatargs = array_slice(func_get_args(), 2);
		$description = vsprintf($descformat, $formatargs);
		
		echo '<div class="error"><h3>' . __($group) . ' ' . __('Fout') . '</h3>';
		echo '<p>' . vsprintf(__($descformat), $formatargs) . '</p>';
		
		if (get_config_value('report_errors', false)) {
			$to = get_config_value('report_to');
			
			$subject = '[WebError] ' . $group;
			$body = $description . "\n\n";
			
			$body .= _get_backtrace('report_error');
			
			if (!mail($to, $subject, $body))
				echo "<p>" . __("De webmaster is NIET op de hoogte gesteld. Stel de webmaster op de hoogte wanneer dit probleem zich voor blijft doen") . "</p>";
			else
				echo "<p>" . __("De webmaster is op de hoogte gesteld van het probleem") . "</p>";
		}
	
		echo '</div>';
	}
	
	/** @group Functions
	  * Try to load a view. Checks if the file exists and if so 
	  * require_once's the file
	  * @file the path of the file to load (relative to include/)
	  *
	  * @result true if the file could be found and loaded, 
	  * false otherwise
	  */
	function _load_view($file) {
		if (!file_exists(dirname(__FILE__) . '/' . $file))
			return false;
		else
			require_once(dirname(__FILE__) . '/' . $file);
		
		return true;
	}
	
	/** @group Functions
	  * Get current theme name
	  * 
	  * @result a string with the current theme name
	  */	
	function get_theme() {
		return get_config_value('theme');
	}
	
	/** @group Functions
	  * Run a view
	  * @name the name of the view. The file <span class="bold">views/<span class="highlight">name</span>.php</span> is included and the function <span class="bold">view_<span class="highlight">name</span></span> is called. Optionally a different view function can be called by using the <span class="highlight">name::function</span> syntax (which will call the	function <span class="bold">view_<span class="highlight">function</span></span> instead)
	  * @model the #DataModel to be used by the view
	  * @iter optional; the #DataIter to be used by the view
	  * @params optional; a hash containing additional parameters
	  */
	function run_view($name, $model = null, $iter = null, $params = null) {
		/* Parse name[::function] */
		$pos = strpos($name, '::');
		
		if ($pos !== false) {
			$function = substr($name, $pos + 2);
			$name = substr($name, 0, $pos);
		} else {
			$function = $name;
		}
		
		
		if ($view = get_new_view($name)) {
			// We need to send the model and iter with the function. However, the new views only take
			// a hash a parameter. Thus, we add the model and iter to the params if possible.
			// Otherwise, throw an error.
			if (is_null($params))
				return $view->$function(array("model" => $model, "iter" => $iter));
				
			if (is_array($params))
				return $view->$function(array_merge($params, array("model" => $model, "iter" => $iter)));

			return report_error(N__("View"), N__("De meegeleverde $params aan functie %s is geen array, maar een: %s"), $name, $params);				
		}
		
		if (!_load_view("../themes/" . get_theme() . "/views/$name.php"))
			if (!_load_view("../themes/default/views/$name.php"))
				return report_error(N__("View"), N__("De view `%s` kan niet gevonden worden (thema: %s)"), $name, get_theme());

		/* Locate the function */
		if (function_exists("view_$function"))
			return call_user_func("view_$function", $model, $iter, $params);
		else
			return report_error(N__("View"), N__("De view functie `%s` in `%s` kan niet gevonden worden (thema: %s)"), $function, $name, get_theme());
	}
	
	/**
	 * Looks for a view in views/name/name.php
	 * If it finds it, it instantiates the class and returns it.
	 *
	 * @view the name of the view
	 *
	 * @result instantiation of the nameView class
	 * @author Pieter de Bie
	 **/
	function get_new_view($view) 
	{
		$view_directory = dirname(__FILE__) . '/../themes/' . get_theme() . '/views/' . $view . '/' . $view . '.php';
		$view_default_directory = dirname(__FILE__) . '/../themes/default/views/' . $view . '/' . $view . '.php';
		
		if (!file_exists($view_directory))
		{
			if (!file_exists($view_default_directory))
				return false;
			else
				$file = $view_default_directory;
		}
		else
			$file = $view_directory;
				
		require_once($file);
		$view_name = $view . 'View';
		if (class_exists($view_name))
			return new $view_name;
				
		return false;

	}
	/** @group Functions
	  * Convenient function to get an array of day names
	  *
	  * @result an array with the day names. The array starts and ends with
	  * sunday
	  */
	function get_days() {
		static $days = null;
		
		if (!$days)
			$days = Array(__('Zondag'), __('Maandag'), __('Dinsdag'), __('Woensdag'), __('Donderdag'), __('Vrijdag'), __('Zaterdag'), __('Zondag'));
		
		return $days;
	}
	
	/** @group Functions
	  * Convenient function to get an array of month names
	  *
	  * @result an array with the month names. Names start with index 1 (
	  * index 0 is the 'none' month)
	  */
	function get_months() {
		static $months = null;
		
		if (!$months)
			$months = Array(__('geen'), __('Januari'), __('Februari'), __('Maart'), __('April'), __('Mei'), __('Juni'), __('Juli'), __('Augustus'), __('September'), __('Oktober'), __('November'), __('December'));
		
		return $months;	
	}

	function get_short_months() {
		static $months = null;
		
		if (!$months)
			$months = Array(__('geen'), __('Jan'), __('Feb'), __('Mrt'), __('Apr'), __('Mei'), __('Jun'), __('Jul'), __('Aug'), __('Sept'), __('Okt'), __('Nov'), __('Dec'));
		
		return $months;	
	}
	
	/** @group Functions
	  * Generate a string with random characters of a certain length
	  * @length optional; the length of the generated string 
	  * (defaults to 8)
	  *
	  * @result a string with random characters
	  */
	function randstr($length = 8) {
		mt_srand((double)microtime() * 10000);

		$ranges = array(1 => array(48, 57), 2 => array(65, 90), 3 => array(97, 122));
		$str = '';

		for ($i = 0; $i < $length; $i++) {
			$x = mt_rand(1,3);
			$str .= chr(mt_rand($ranges[$x][0], $ranges[$x][1]));
		}

		return $str;
	}

	/** @group Functions
	  * Create properly encoded query string from $_SERVER['PHP_SELF'] 
	  * and $_GET leaving out a set of variables. You can specify which
	  * variables to leave out of the query string with the function
	  * parameters (every parameter specifies a variable name to be left
	  * out the query string)
	  *
	  * @result a query string in the form of <file>?var1=val1&var2=val2...
	  */
	function get_request() {
		$self = $_SERVER['PHP_SELF'];
		$args = func_get_args();
		$query = '';

		foreach ($_GET as $key => $value)
			if (!in_array($key, $args)) {
				if ($query == '')
					$query .= '?';
				else
					$query .= '&';

				$query .= urlencode($key) . '=' . urlencode($value);
			}
		
		return $self . $query;
	}

	/**
	 * Format a string with php-style variables with optional modifiers.
	 * 
	 * Format description:
	 *     $var            Will be replaced by the value of $params['var'].
	 *     $var|modifier   Will be replaced by the value of modifier($params['var'])
	 *
	 * Example:
	 *     format_string('This is the $day|ordinal day', array('day' => 5))
	 *     results in "This is the 5th day"
	 *
	 * @param string $format the format of the string
	 * @param array $params a table of variables that will be replaced
	 * @return string a formatted string in which all the variables are replaced
	 * as far as they can be found in $params.
	 */
	function format_string($format, array $params)
	{
		$callback =  function($match) use ($params) {
			// If this key does not exist, just return the matched pattern
			if (!isset($params[$match[1]]))
				return $match[0];

			// Find the value for this key
			$value = $params[$match[1]];

			// If there is a modifier, apply it
			if (isset($match[2])) {
				$value = call_user_func($match[2], $value);
			}

			return $value;
		};

		return preg_replace_callback('/\$([a-z][a-z0-9_]*)(?:\|([a-z]+))?\b/i', $callback, $format);
	}
	
	/** @group Functions
	  * Add GET query string variable. The purpose of this function is
	  * add ? or & whenever needed (so for example str = 'index.php',
	  * add = 'var=yes' and str = 'index.php?', add = 'var=yes' will
	  * result in 'index.php?var=yes'. The other case is when
	  * str = 'index.php?test=no' and add = 'var=yes', which results in
	  * 'index.php?test=no&amp;var=yes
	  * @str the query string to add a variable to
	  * @add a string of the form <var>=<val>
	  *
	  * @result the resulting query string.
	  */
	function add_request($str, $add) {
		$base = basename($str);
		
		if (!preg_match('/[^\?]+(\?$|\?.+)/', $base, $matches))
			return $str . '?' . $add;
		
		if (strlen($matches[1]) == 1)
			return $str . $add;
		else
			return $str . '&' . $add;
	}

	/**
	 * Shortcut to add and remove query parameters from urls. First all parameters
	 * named in $remove are removed, then parameters from $add are recursively
	 * merged with the existing parameters in the url.
	 * 
	 * @param string $url the url to edit
	 * @param string[] $add key-value pairs of query parameters to add to the url
	 * @param string[] $remove keys of query parameters to remove.
	 * @return string
	 */
	function edit_url($url, array $add = array(), array $remove = array())
	{
		$query_start = strpos($url, '?');

		$fragment_start = strpos($url, '#');

		$query_end = $fragment_start !== false
			? $fragment_start
			: strlen($url);

		if ($query_start !== false)
			parse_str(substr($url, $query_start + 1, $query_end - $query_start), $query);
		else
			$query = array();

		foreach ($remove as $key)
			if (isset($query[$key]))
				unset($query[$key]);

		$query = array_merge_recursive($query, $add);

		$query_str = http_build_query($query);

		$out = $query_start !== false
			? substr($url, 0, $query_start)
			: $url;

		if ($query_str != '')
			$out .= '?' . $query_str;

		if ($fragment_start !== false)
			$out .= substr($url, $fragment_start);

		return $out;
	}
	
	/** @group Functions
	  */
	function agenda_time_for_display($iter, $field) {
		if ($iter->get($field . 'uur') == 0 && $iter->get($field . 'minuut') == 0)
			return '';
		else
			return sprintf('%02d:%02d', $iter->get($field . 'uur'), $iter->get($field . 'minuut'));
	}

	/** @group Functions
	  * Get a string for display of the van -> tot of an agendapunt
	  * @iter a #Dataiter of an agendapunt
	  *
	  * @result a string ready for display
	  */
	function agenda_period_for_display($iter) {
		// If there is no till date, leave it out
		if (!$iter->get('tot') || $iter->get('tot') == $iter->get('van')) {
			
			// There is no time specified
			if ($iter->get('vanuur') + 0 == 0)
				$format = __('$from_dayname $from_day $from_month');
			else
				$format = __('$from_dayname $from_day $from_month, $from_time');
		}

		/* Check if date part (not time) is not the same */
		else if (substr($iter->get('van'), 0, 10) != substr($iter->get('tot'), 0, 10)) {
			$format = __('$from_dayname $from_day $from_month $from_time tot $till_dayname $till_day $till_month $till_time');
		} else {
			$format = __('$from_dayname $from_day $from_month, $from_time tot $till_time');
		}

		$days = get_days();
		$months = get_months();
		
		return format_string($format, array(
			'from_dayname' => $days[$iter->get('vandagnaam')],
			'from_day' => $iter->get('vandatum'),
			'from_month' => $months[$iter->get('vanmaand')],
			'from_time' => agenda_time_for_display($iter, 'van'),
			'till_dayname' => $days[$iter->get('totdagnaam')],
			'till_day' => $iter->get('totdatum'),
			'till_month' => $months[$iter->get('totmaand')],
			'till_time' => agenda_time_for_display($iter, 'tot')
		));
	}

	function agenda_short_period_for_display($iter)
	{	
		$months = get_short_months();

		// Same time? Only display start time.
		if ($iter->get('van') == $iter->get('tot'))
			$format = __('vanaf $from_time');

		// Not the same end date? Show the day range instead of the times
		elseif ($iter->get('vandatum') != $iter->get('totdatum') - ($iter->get('totuur') < 10 ? 1 : 0))
		{
			// Not the same month? Add month name as well
			if ($iter->get('vanmaand') != $iter->get('totmaand'))
				$format = __('$from_day $from_month tot $till_day $till_month');
			else
				$format = __('$from_day tot $till_day $till_month');
		}
		else
			$format = __('$from_time tot $till_time');

		return format_string($format, array(
			'from_day' => $iter->get('vandatum'),
			'from_month' => $months[$iter->get('vanmaand')],
			'from_time' => agenda_time_for_display($iter, 'van'),
			'till_day' => $iter->get('totdatum'),
			'till_month' => $months[$iter->get('totmaand')],
			'till_time' => agenda_time_for_display($iter, 'tot')
		));
	}
	
	/** @group Functions
	  * Parse an email message and substitute variables and constants. The 
	  * function will first look for email in themes/<theme>/email and will
	  * fallback to the default theme if the file could not be found
	  * @email the email file to parse
	  * @data the data to substitute
	  *
	  * @result A string with substituted data and constants or false
	  * if the specified email file could not be found
	  */
	function parse_email($email, $data) {
		if (file_exists('themes/' . get_theme() . '/email/' . $email))
			$contents = file('themes/' . get_theme() . '/email/' . $email);
		elseif (get_theme() != 'default') /* Fallback on default theme */
			if (file_exists('themes/default/email/' . $email))
				$contents = file('themes/default/email/' . $email);
			else {
				report_error(N__("Email"), N__("De email `%s` kan niet gevonden worden (thema: %s)"), $email, get_theme());
				return false;
			}
		else {
			report_error(N__("Email"), N__("De email `%s` kan niet gevonden worden (thema: %s)"), $email, get_theme());
			return false;
		}

		$s = '';
		
		foreach ($contents as $line) {
			// FIXME: use format_string here which is much, much safer than evaluating regular expressions.
			$line = preg_replace('/([A-Z]+_[A-Z_]+)/e', 'constant(\'\1\')', $line);
			$line = preg_replace('/\$([a-z][a-z0-9_]*)/ei', '$data[\'\1\']', $line);
			
			$s .= $line;
		}
		
		return $s;
	}
	
	function get_theme_data($path) {
		if (!file_exists('themes/' . get_theme() . '/' . $path) || get_theme() == 'default')
			return 'themes/default/' . $path;
		else
			return 'themes/' . get_theme() . '/' . $path;
	}
	
	/** @group Functions
	  * Generate a themed image element
	  * @src the image source (theme path will be prepended automatically
	  * @alt optional; the image alternative text
	  * @title optional; the images title
	  * @rest optiona; other attributes to add to the element
	  *
	  * @result an im element
	  */
	function image($src, $alt = '', $title = '', $rest = '') {
		$f = get_theme_data('images/' . $src);

		return '<img src="' . $f . '" alt="' . $alt . '" title="' . $title . '" ' . $rest . '/>';
	}
	
	/** @group Functions
	  * Format a date in respecting locale settings
	  * @date the string in database date format (yyyy-mm-dd)
	  *
	  * @result the date formatted in current locale
	  */
	function format_date($date) {
		$date = explode('-', $date);
		
		if (count($date) != 3)
			return 'nee';

		return sprintf("%02d-%02d-%d", intval($date[2]), intval($date[1]), intval($date[0]));
	}

	/** @group Functions
	  * Format a field name for display (replace _ with spaces and 
	  * capitalize the first word)
	  * @field the field to create a display string for
	  *
	  * @result the field ready for display
	  */
	function field_for_display($field) {
		if (!$field)
			return '';

		$words = explode('_', $field);
		$first = array_shift($words);
		
		return __(ucfirst($first) . (count($words) > 0 ? (' ' . implode(' ', $words)) : ''));
	}
	
	/** @group Functions
	  * Implode a list while separating it with , (except for the last item
	  * for which "and" is used instead of a comma
	  * @list the list to implode
	  *
	  * @result a string in the format item1, item2 and item3
	  */
	function implode_human($list) {
		$len = count($list);
		
		if ($len == 0)
			return '';
		elseif ($len == 1)
			return $list[0];
		else
			return implode(', ', array_slice($list, 0, $len - 1)) . ' ' . __('en') . ' ' . $list[$len - 1];
	}
	
	/** @group Functions
	  * Normalizes an url (putting http:// in front if needed)
	  * @url the url to normalize
	  *
	  * @result the normalized url
	  */
	function normalize_url($url) {
		if (!preg_match('/^[a-z]+:\/\//', $url))
			return 'http://' . $url;
		else
			return $url;
	}
	
	/** @group Functions
	  * Get forum post author url
	  * @message the forum message iter
	  * @last optional; whether or not get the last author
	  * 
	  * @result the url
	  */
	function get_author_link($message, $last = false) {
		if ($last && $message->get('last_author_type'))
			$field = 'last_author';
		else
			$field = 'author';

		$type = intval($message->get($field . '_type'));
		
		switch ($type) {
			case 1: /* Person */
				return 'profiel.php?lid=' . $message->get($field);
			break;
			case 2: /* Commissie */
				$commissie_model = get_model('DataModelCommissie');
				$page = $commissie_model->get_page($message->get($field));
				
				if ($page !== null)
					return 'show.php?id=' . $page;
			break;
		}
		
		return '';
	}
	
	/** @group Functions
	  * Get the first n words
	  * @s a string
	  * @num the number of words
	  *
	  * @result a string with only num words
	  */
	function first_words($s, $num) {
		if (($m = preg_match('/^(\b.+?\b\W*){1,4}/i', $s, $matches)))
			return trim($matches[0]);
		
		return $s;
	}

	/** @group Functions
	  * Create a pronouncable password string
	  * 
	  * @result a string
	  */
	function create_pronouncable_password() {
		static $vowels = Array('a','e','i','o','u');
		static $consonants = Array('b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','z');

		$pass = '';

		for ($i = 0; $i < 3; $i++)
			$pass .= $consonants[array_rand($consonants)] . $vowels[array_rand($vowels)] . $consonants[array_rand($consonants)];

		return $pass;
	}

	function find_file(array $search_paths)
	{
		foreach ($search_paths as $path)
			if (file_exists($path))
				return $path;

		return null;
	}

	function image_aspect($width, $height = null)
	{
		if (is_array($width) && $height === null)
			return image_aspect($width[0], $width[1]);

		if ($width == $height)
			return 'square';
		elseif ($width > $height)
			return 'landscape';
		else
			return 'portrait';
	}

	function parse_http_accept($header, array $available = array())
	{
		$accepted = array();

		foreach (explode(',', $header) as $type)
		{
			$type = trim($type);

			if (preg_match('/;q=(\d+(?:\.\d+)?)$/', $type, $match))
				$weight = floatval($match[1]);
			else
				$weight = 1.0;

			$accepted[] = $type;
			$weights[] = $weight;
		}

		array_multisort($weights, SORT_NUMERIC, SORT_DESC, $accepted);

		if (count($available) > 0)
			foreach ($accepted as $preferred)
				if (in_array($preferred, $available))
					return $preferred;

		return $available;
	}

	function strip_protocol($url)
	{
		return preg_replace('/^https?:/', '', $url);
	}

	function set_domain_cookie($name, $value, $cookie_time = 0)
	{
		// Determine the host name for the cookie (try to be as broad as possible so sd.svcover.nl can profit from it)
		if (preg_match('/([^.]+)\.(?:[a-z\.]{2,6})$/i', $_SERVER['HTTP_HOST'], $match))
			$domain = $match[0];
		else if ($_SERVER['HTTP_HOST'] != 'localhost')
			$domain = $_SERVER['HTTP_HOST'];
		else
			$domain = null;

		// If the value is empty, expire the cookie
		if ($value === null)
			$cookie_time = 1;

		setcookie($name, $value, $cookie_time, '/', $domain);

		if ($cookie_time === 0 || $cookie_time > time())
			$_COOKIE[$name] = $value;
		else
			unset($_COOKIE[$name]);
	}

	/**
	 * Implementation of array_search that supports a user-defined compare function.
	 * Returns the key or index at which $needle is found in $haystack. If needle
	 * is not found, it returns NULL.
	 * 
	 * @var $needle The item searched for
	 * @var array $haystack The array to search in (list or hashtable)
	 * @var callable $compare_function A compare function that gets $needle and an item
	 *      from $haystack and should return true if they are 'equal' or false otherwise.
	 * @return mixed the key at which $needle is found. Returns NULL if $needle is not found
	 */
	function array_usearch($needle, array $haystack, $compare_function)
	{
		foreach ($haystack as $i => $item)
			if (call_user_func($compare_function, $needle, $item))
				return $i;

		return null;
	}

	/**
	 * Concatenates multiple path parts together with a directory separator (/) between them.
	 *
	 * @var string $path_component
	 * @var string ...
	 * @return string the concatenated path
	 */
	function path_concat($path_component)
	{
		$path = '';
		
		foreach (func_get_args() as $path_component)
		{
			if (strlen($path) === 0)
				$path .= rtrim($path_component, '/');
			else
				$path .= '/' . trim($path_component, '/');
		}

		return $path;
	}

	function path_subtract($full_path, $basedir)
	{
		if (substr($full_path, 0, strlen($basedir)) != $basedir)
			throw new InvalidArgumentException('Full path is not a path inside the given base directory');

		return ltrim(substr($full_path, strlen($basedir)), '/');
	}

	function crc32_file($path)
	{ 
		return hash_file('CRC32', $path, false);
	}

	function encode_data_uri($mime_type, $data)
	{
		return 'data:' . $mime_type . ';base64,' . base64_encode($data);
	}