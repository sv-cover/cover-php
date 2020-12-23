<?php
	if (!defined('IN_SITE'))
		return;

	require_once 'include/config.php';
	require_once 'include/exception.php';
	require_once 'include/data.php';
	require_once 'include/view.php';

	function _dump(...$args) {
		ob_start();
		var_dump(...$args);
		$data = ob_get_clean();
		printf('<code style="overflow-x:scroll;display:block;background:white;color: black;padding:10px;border:1px solid black;"><pre>%s</pre></code>', $data);
		return $args[0];
	}

	function _get_backtrace() {
		$skip = func_get_args();
		$skip[] = '_get_backtrace';
		$result = '';

		foreach (debug_backtrace() as $trace) {
			if (!in_array($trace['function'], $skip)) {
				if (isset($trace['file']))
					$result .= $trace['file'] . "\n";
				if (isset($trace['class']))
					$result .= $trace['class'] . '::';
				
				$args = '';
				
				foreach ($trace['args'] as $arg) {
					if ($args != '')
						$args .= ', ';
					
					$args .= @var_export($arg, true);
				}
				
				$result .= $trace['function'] . ' (' . $args . ')';
				if (isset($trace['line']))
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
		
		echo '<div class="error"><h3>' . __($group) . ' ' . __('Error') . '</h3>';
		echo '<p>' . vsprintf(__($descformat), $formatargs) . '</p>';
		
		if (get_config_value('report_errors', false)) {
			$to = get_config_value('report_to');
			
			$subject = '[WebError] ' . $group;
			$body = $description . "\n\n";
			
			$body .= _get_backtrace('report_error');
			
			if (!mail($to, $subject, $body))
				echo "<p>" . __("The webmaster has NOT been informed. Please inform the webmaster if this problem keeps occurring") . "</p>";
			else
				echo "<p>" . __("The webmaster has been notified of the problem") . "</p>";
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
		
		
		try {
			$view = View::byName($name);
			
			if (is_null($params))
				return $view->$function(array("model" => $model, "iter" => $iter));
				
			if (is_array($params))
				return $view->$function(array_merge($params, array("model" => $model, "iter" => $iter)));

			throw new InvalidArgumentException(sprintf(__("De meegeleverde $params aan functie %s is geen array, maar een: %s"), $name, $params));
		} catch(ViewNotFoundException $e) {
			if (!_load_view("../themes/" . get_theme() . "/views/$name.php"))
				if (!_load_view("../themes/default/views/$name.php"))
					throw new ViewNotFoundException(sprintf(__("The view %s could not be found (theme: %s)"), $name, get_theme()));

			/* Locate the function */
			if (function_exists("view_$function"))
				return call_user_func("view_$function", $model, $iter, $params);
			else
				throw new ViewNotFoundException(sprintf(__("The view function %s in %s could not be found (theme: %s)"), $function, $name, get_theme()));
		}
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
		return View::byName($view, null);
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
			$days = Array(__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday'), __('Sunday'));
		
		return $days;
	}

	function get_short_days() {
		static $days = null;
		
		if (!$days)
			$days = Array(__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat'), __('Sun'));
		
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
			$months = Array(__('no'), __('January'), __('February'), __('March'), __('April'), __('May'), __('June'), __('July'), __('August'), __('September'), __('October'), __('November'), __('December'));
		
		return $months;	
	}

	function get_short_months() {
		static $months = null;
		
		if (!$months)
			$months = Array(__('no'), __('Jan'), __('Feb'), __('Mar'), __('Apr'), __('May'), __('Jun'), __('Jul'), __('Aug'), __('Sept'), __('Oct'), __('Nov'), __('Dec'));
		
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
		$length = ($length < 4) ? 4 : $length;
        return bin2hex(random_bytes(($length-($length%2))/2));
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
	function format_string($format, $params)
	{
		if (!(is_array($params) || $params instanceof ArrayAccess))
			throw new \InvalidArgumentException('$params has to behave like an array');

		$callback =  function($match) use ($params) {
			$path = explode('[', $match[1]);

			// remove ] from all 1..n components
			for ($i = 1; $i < count($path); ++$i)
				$path[$i] = substr($path[$i], 0, -1);

			// Step 0
			$value = $params;

			foreach ($path as $step) {
				if (isset($value[$step])) {
					$value = $value[$step];
				} else {
					$value = null;
					break;
				}
			}

			// If there is a modifier, apply it
			if (isset($match[2]))
				$value = call_user_func($match[2], $value);

			return $value;
		};

		return preg_replace_callback('/\$([a-z][a-z0-9_]*(?:\[[a-z0-9_]+\])*)(?:\|([a-z_]+))?/i', $callback, $format);
	}

	function optional($value)
	{
		return strlen($value) > 0 ? ' ' . $value : '';
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
	function agenda_time_for_display(DataIterAgenda $iter, $field)
	{
		$hour = intval($iter[$field . '_datetime']->format('G'), 10);
		$minute = intval($iter[$field . '_datetime']->format('i'), 10);

		if ($hour == 0 && $minute == 0)
			return '';
		else
			return sprintf('%02d:%02d', $hour, $minute);
	}

	/** @group Functions
	  * Get a string for display of the van -> tot of an agendapunt
	  * @iter a #Dataiter of an agendapunt
	  *
	  * @result a string ready for display
	  */
	function agenda_period_for_display(DataIterAgenda $iter)
	{
		// If there is no till date, leave it out
		if (!$iter->get('tot') || $iter->get('tot') == $iter->get('van')) {
			
			// There is no time specified
			if ($iter->get('van_datetime')->format('G') == 0)
				$format = __('$from_dayname $from_day|ordinal of $from_month');
			else
				$format = __('$from_dayname $from_day|ordinal of $from_month, $from_time');
		}

		/* Check if date part (not time) is not the same */
		else if (substr($iter->get('van'), 0, 10) != substr($iter->get('tot'), 0, 10)) {
			$format = __('$from_dayname $from_day|ordinal of $from_month $from_time till $till_dayname $till_day|ordinal of $till_month $till_time');
		} else {
			$format = __('$from_dayname $from_day|ordinal of $from_month, $from_time till $till_time');
		}

		return agenda_format_period($iter, $format);
	}

	function agenda_short_period_for_display(DataIterAgenda $iter)
	{	
		$months = get_short_months();

		// Same time? Only display start time.
		if ($iter->get('van') == $iter->get('tot'))
			$format = __('starts at $from_time');

		// Not the same end date? Show the day range instead of the times
		elseif ($iter->get('van_datetime')->format('w') != $iter->get('tot_datetime')->format('w') - ($iter->get('tot_datetime')->format('G') < 10 ? 1 : 0))
		{
			// Not the same month? Add month name as well
			if ($iter->get('van_datetime')->format('n') != $iter->get('tot_datetime')->format('n'))
				$format = __('$from_month $from_day|ordinal till $till_month $till_day|ordinal');
			else
				$format = __('$from_day|ordinal till $till_day|ordinal of $till_month');
		}
		else
			$format = __('$from_time till $till_time');

		return agenda_format_period($iter, $format);
	}

	/**
	 * Format the period of a calendar item according to $format. You can use 
	 * variables in the form $aaa_bbb where aaa is either 'from' or 'till', and
	 * bbb is 'dayname', 'day', 'month' or 'time'. The formatting is done using
	 * format_string, so you can also use modifiers such as |ordinal.
	 * The function returns HTML including <time> elements that adhere to the
	 * h-event microformat.
	 *
	 * Example usage:
	 * 
	 *    agenda_format_period($iter, '$from_day|ordinal to $till_day|ordinal $till_month')
	 *
	 * @param DataIterAgenda calendar item
	 * @param string formatting string
	 * @return string formatted html
	 */
	function agenda_format_period(DataIterAgenda $iter, $format)
	{
		$days = get_days();
		$short_days = get_short_days();
		$months = get_months();
		$short_months = get_short_months();

		$van = $iter['van_datetime'];
		$tot = $iter['tot_datetime'];

		$format = preg_replace(
			[
				'/(\$from_[a-z_]+(\|[a-z]+)?\b)(.+\$from_[a-z_]+(\|[a-z]+)?\b)?/',
				'/(\$till_[a-z_]+(\|[a-z]+)?\b)(.+\$till_[a-z_]+(\|[a-z]+)?\b)?/'
			],
			[
				'<time class="dt-start" datetime="' . $van->format('Y-m-d H:i') . '">$0</time>',
				'<time class="dt-end" datetime="' . $tot->format('Y-m-d H:i') . '">$0</time>'
			], 
			$format);
		
		return format_string($format, array(
			'from_dayname' => $days[$van->format('w')],
			'from_dayname_short' => $short_days[$van->format('w')],
			'from_day' => $van->format('j'),
			'from_month' => $months[$van->format('n')],
			'from_month_short' => $short_months[$van->format('n')],
			'from_time' => agenda_time_for_display($iter, 'van'),
			'till_dayname' => $days[$tot->format('w')],
			'till_dayname_short' => $short_days[$tot->format('w')],
			'till_day' => $tot->format('j'),
			'till_month' => $months[$tot->format('n')],
			'till_month_short' => $short_months[$tot->format('n')],
			'till_time' => agenda_time_for_display($iter, 'tot')
		));
	}
	
	/**
	  * Parse an email message and substitute variables and constants. The 
	  * function will first look for email in themes/<theme>/email and will
	  * fallback to the default theme if the file could not be found
	  * @param string the name of the email file to parse
	  * @param array the data to substitute
	  *
	  * @return string A string with substituted data and constants
	  */
	function parse_email($email, $data)
	{
		if (file_exists('themes/' . get_theme() . '/email/' . $email))
			$contents = file_get_contents('themes/' . get_theme() . '/email/' . $email);
		elseif (get_theme() != 'default' && file_exists('themes/default/email/' . $email))
			$contents = file_get_contents('themes/default/email/' . $email);
		else
			throw new RuntimeException("Could not find email template '$email'");

		$contents = preg_replace_callback('/[A-Z]+_[A-Z_]+/', function($match) { return constant($match[0]); }, $contents);
		
		return format_string($contents, $data);
	}

	class SimpleEmail
	{
		public $subject;

		public $headers;

		public $body;

		public function __construct($subject, $body, $headers)
		{
			$this->subject = $subject;
			$this->body = $body;
			$this->headers = $headers;
		}

		public function send($recipient)
		{
			return mail($recipient, $this->subject, $this->body, $this->headers);
		}
	}

	function parse_email_object($file, array $variables = array())
	{
		$path = get_theme_data('email/' . $file, false);

		if (!file_exists($path))
			throw new InvalidArgumentException("Cannot find file '{$file}' in any theme data");

		$file_body = file_get_contents($path);

		if (!$file_body)
			throw new InvalidArgumentException("File '{$path}' is unreadable or empty");

		$subject = null;

		$headers = array();

		$body = '';

		$parsing_headers = true;

		foreach (preg_split("/\r?\n/", $file_body) as $line) {
			if (preg_match("/^([a-z0-9_-]+):\s+(.+?)$/i", $line, $match)) {
				// Remove newlines from header values because they mess up other headers
				// (Better would be to indent them with spaces, but that is probably never really needed.)
				$header_value = preg_replace('/(\r?\n)+/', ' ', format_string($match[2], $variables));
				
				if (strcasecmp($match[1], 'Subject') === 0)
					$subject = $header_value;
				else
					$headers[] = sprintf("%s: %s", $match[1], $header_value);
			}
			else
				$parsing_headers = false;

			if (!$parsing_headers)
				$body .= format_string($line, $variables) . "\r\n";
		}

		return new SimpleEmail($subject, ltrim($body), implode("\r\n", $headers));
	}

	
	function get_theme_data($file, $include_filemtime = true) {
		if (!file_exists('themes/' . get_theme() . '/' . $file) || get_theme() == 'default')
			$path = 'themes/default/' . $file;
		else
			$path = 'themes/' . get_theme() . '/' . $file;

		if ($include_filemtime && file_exists($path))
			$path .= '?' . filemtime($path);

		return $path;
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
	function implode_human($list)
	{
		$len = count($list);
		
		if ($len === 0)
			return '';
		elseif ($len === 1)
			return reset($list);
		else
			return implode(', ', array_slice($list, 0, $len - 1)) . ' ' . __('and') . ' ' . end($list);
	}

	function human_file_size($bytes, $decimals = 2)
	{
		$size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
		$factor = (int) floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size[$factor];
	}

	function format_date_relative($time)
	{
		if (!is_int($time) && !ctype_digit($time))
			$time = strtotime($time);
		
		$diff = time() - $time;

		if ($diff == 0)
			return __('now');

		else if ($diff > 0)
		{
			$day_diff = floor($diff / 86400);
			
			if ($day_diff == 0)
			{
				if ($diff < 60) return __('less than a minute ago');
				if ($diff < 120) return __('1 minute ago');
				if ($diff < 3600) return sprintf(__('%d minutes ago'), floor($diff / 60));
				if ($diff < 7200) return __('1 hour ago');
				if ($diff < 86400) return sprintf(__('%d hours ago'), floor($diff / 3600));
			}
			if ($day_diff == 1) return __('Yesterday');
			if ($day_diff < 7) return sprintf(__('%d days ago'), $day_diff);
			// if ($day_diff < 31) return sprintf(__('%d weken geleden'), floor($day_diff / 7));
			// if ($day_diff < 60) return __('afgelopen maand');
			return date('j-n-Y', $time);
		}
		else
			return date('j-n-Y', $time);
	}

	function format_table(array $data)
	{
		$rows = [];

		foreach ($data as $key => $value)
			$rows[] = sprintf('<tr><th style="text-align:left">%s</th><td>%s</td></tr>', markup_format_text($key), markup_format_text($value));

		return sprintf('<table>%s</table>', implode('', $rows));
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

		$domain = preg_replace('/:\d+$/', '', $domain);

		// If the value is empty, expire the cookie
		if ($value === null)
			$cookie_time = 1;

		$secure = !empty($_SERVER['HTTPS']);

		$http_only = true;

		setcookie($name, $value, $cookie_time, '/', $domain, $secure, $http_only);

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
	 * @var mixed $needle The item searched for
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
	function path_concat($path_components)
	{
		$path_components = func_get_args();

		$path = '';
		
		foreach ($path_components as $path_component)
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

	function sanitize_filename($string)
	{
		// Source: http://stackoverflow.com/a/2727693
		return preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $string);
	}

	function crc32_file($path)
	{ 
		return hash_file('CRC32', $path, false);
	}

	function encode_data_uri($mime_type, $data)
	{
		return 'data:' . $mime_type . ';base64,' . base64_encode($data);
	}

	function array_sample(array $input, $sample_size)
	{
		shuffle($input);
		return array_slice($input, 0, $sample_size);
	}

	function curry_call_method($method, $arguments = [])
	{
		$arguments = func_get_args();
		array_shift($arguments);
		
		return function($object) use ($method, $arguments) {
			return call_user_func_array([$object, $method], $arguments);
		};
	}

	function ends_with($haystack, $needle)
	{
		return substr_compare($haystack, $needle, -strlen($needle));
	}

	function is_same_domain($subdomain, $domain, $levels = 2)
	{
		$sub = explode('.', $subdomain);
		$top = explode('.', $domain);

		$levels = min($levels, count($sub), count($top));

		while ($levels-- > 0)
			if (array_pop($sub) != array_pop($top))
				return false;

		return true;
	}


	/**
	 * Really really simple mail function for attachments that barely uses any memory
	 * because it streams like everything!
	 */
	function send_mail_with_attachment($to, $subject, $message, $additional_headers, array $attachments)
	{
		$fout = popen(ini_get('sendmail_path') . ' -oi', 'w');

		if (!$fout)
			throw new Exception("Could not open sendmail");

		$boundary = md5(microtime());

		// Headers and dummy message
		fwrite($fout,
			"MIME-Version: 1.0\r\n"
			. ($additional_headers ? (trim($additional_headers, "\r\n") . "\r\n") : "")
			. "To: $to\r\n"
			. "Subject: $subject\r\n"
			. "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n"
			. "\r\n"
		. "This is a mime-encoded message"
			. "\r\n\r\n");

		// Message content
		fwrite($fout, "--$boundary\r\n"
			. "Content-Type: text/plain; charset=\"UTF-8\"\r\n"
			. "Content-Transfer-Encoding: quoted-printable\r\n\r\n");

		$filter = stream_filter_append($fout, 'convert.quoted-printable-encode',
			STREAM_FILTER_WRITE, ["line-length" => 80, "line-break-chars" => "\r\n"]);

		if (is_resource($message))
			stream_copy_to_stream($message, $fout);
		else
			fwrite($fout, $message);

		stream_filter_remove($filter);

		fwrite($fout, "\r\n");

		foreach ($attachments as $file_name => $file)
		{
			$file_handle = is_resource($file) ? $file : fopen($file, 'rb');
			// Attachment
			fwrite($fout, "\r\n--$boundary\r\n"
				. "Content-Type: application/octet-stream; name=\"" . addslashes($file_name) . "\"\r\n"
				. "Content-Transfer-Encoding: base64\r\n"
				. "Content-Disposition: attachment\r\n\r\n");

			$filter = stream_filter_append($fout, 'convert.base64-encode',
				STREAM_FILTER_WRITE, ["line-length" => 80, "line-break-chars" => "\r\n"]);

			stream_copy_to_stream($file_handle, $fout);

			stream_filter_remove($filter);

			fclose($file_handle);
		}

		fwrite($fout, "\r\n--$boundary--\r\n");

		fclose($fout);
	}

	function array_one(array $elements, $test)
	{
		foreach ($elements as $index => $element)
			if (call_user_func($test, $element, $index))
				return true;

		return false;
	}

	function array_all(array $elements, $test)
	{
		foreach ($elements as $index => $element)
			if (!call_user_func($test, $element, $index))
				return false;

		return true;
	}

	function array_find(array $elements, $test)
	{
		foreach ($elements as $index => $element)
			if (call_user_func($test, $element, $index))
				return $element;

		return null;
	}

	function array_group_by($array, $key_accessor)
	{
		$groups = array();

		foreach ($array as $element) {
			$key = (string) call_user_func($key_accessor, $element);
			if (isset($groups[$key]))
				$groups[$key][] = $element;
			else
				$groups[$key] = [$element];
		}

		return $groups;
	}

	function array_select($array, $property, $default_value = null)
	{
		return array_map(function($iter) use ($property, $default_value) {
			return isset($iter[$property]) ? $iter[$property] : $default_value;
		}, $array);
	}

	/**
	 * Follow a path through $array. The path can contain array index operators and
	 * object property accessors, similar to PHP's syntax.
	 *
	 * Examples:
	 *   options[0]->value
	 *   options[0][test][4]
	 *   options
	 *
	 * @param mixed $array the source of the data
	 * @param string $path the path to follow
	 * @param mixed $default_value the value returned if the path does not exist.
	 * @return mixed the data in $array at the end of $path, or $default_value if that path did not exist.
	 */
	function array_path($array, $path, $default_value = null)
	{
		// Construct the path
		if (!preg_match('/^(?P<head>[\w-]+)(?P<rest>(\[[\w-]+\]|->\w+)*)$/', $path, $match))
			throw new InvalidArgumentException("The path '$path' is malformed");

		$steps = [['index' => $match['head']]];

		if (!empty($match['rest'])) {
			if (!preg_match_all('/\[(?P<index>[\w-]+)\]|->(?P<property>\w+)/', $match['rest'], $match, PREG_SET_ORDER))
				throw new InvalidArgumentException('The rest of the path is malformed');

			$steps = array_merge($steps, $match);
		}

		// Unwind the path
		foreach ($steps as $step) {
			if (isset($step['property'])) {
				if (!isset($array->{$step['property']}))
					return $default_value;
				else
					$array = $array->{$step['property']};
			} else {
				if (!isset($array[$step['index']]))
					return $default_value;
				else
					$array = $array[$step['index']];
			}
		}

		return $array;
	}

	function getter($key, $default = null)
	{
		return function($map) use ($key, $default) {
			return isset($map[$key]) ? $map[$key] : $default;
		};
	}

	if (!function_exists('hash_equals'))
	{
		function hash_equals($str1, $str2)
		{
			if (strlen($str1) != strlen($str2))
				return false;
			
			$res = $str1 ^ $str2;
			$ret = 0;

			for ($i = strlen($res) - 1; $i >= 0; $i--)
				$ret |= ord($res[$i]);
			
			return !$ret;
		}
	}

	function summarize($text, $length)
	{
		$text = trim($text);

		if (strlen($text) < $length)
			return $text;

		$summary = substr($text, 0, $length);

		if (!in_array(substr($summary, -1, 1), ['.', ' ', '!', '?']))
			$summary = substr($summary, 0, -1) . '…';

		return $summary;
	}

	function apply_image_orientation(\Imagick $image, $background_color = '#000')
	{
		// Copied from https://stackoverflow.com/a/31943940/770911

		$orientation = $image->getImageOrientation();

		// See https://www.daveperrett.com/articles/2012/07/28/exif-orientation-handling-is-a-ghetto/
		switch ($image->getImageOrientation())
		{
			case \Imagick::ORIENTATION_TOPLEFT:
				break;
			case \Imagick::ORIENTATION_TOPRIGHT:
				$image->flopImage();
				break;
			case \Imagick::ORIENTATION_BOTTOMRIGHT:
				$image->rotateImage($background_color, 180);
				break;
			case \Imagick::ORIENTATION_BOTTOMLEFT:
				$image->flopImage();
				$image->rotateImage($background_color, 180);
				break;
			case \Imagick::ORIENTATION_LEFTTOP:
				$image->flopImage();
				$image->rotateImage($background_color, -90);
				break;
			case \Imagick::ORIENTATION_RIGHTTOP:
				$image->rotateImage($background_color, 90);
				break;
			case \Imagick::ORIENTATION_RIGHTBOTTOM:
				$image->flopImage();
				$image->rotateImage($background_color, 90);
				break;
			case \Imagick::ORIENTATION_LEFTBOTTOM:
				$image->rotateImage($background_color, -90);
				break;
			default: // Invalid orientation
				break;
		}
		
		$image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
	} 

	function strip_exif_data(\Imagick $image)
	{
		// Safe the color profiles because those we want to keep 
		$profiles = $image->getImageProfiles('icc', true);

		// Strip all the exif info (including orientation!)
		$image->stripImage();

		// Reset those profiles (if there were any in the first place)
		if ($profiles)
    		$image->profileImage('icc', $profiles['icc']);
	}

	function is_safe_redirect($redirect)
	{
		$redirect_parts = parse_url($redirect);
		return in_array($redirect_parts['scheme'], ['http', 'https'])
			&& $redirect_parts['host'] == $_SERVER['HTTP_HOST'];
	}

	function get_filemanager_url($path, $width=null)
	{
		$filemanager_root = get_config_value('filemanager_root', 'https://filemanager.svcover.nl');
		if (!$width)
			return sprintf('%s/%s', $filemanager_root, $path);
		return sprintf('%s/images/resize?f=%s&w=%d', $filemanager_root, urlencode($path), $width);
	}
