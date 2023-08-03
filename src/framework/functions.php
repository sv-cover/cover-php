<?php
if (!defined('IN_SITE'))
	return;

require_once 'src/framework/config.php';
require_once 'src/framework/exception.php';
require_once 'src/framework/data/data.php';
require_once 'src/framework/view.php';


/** @group Functions
  * Generate a string with random characters of a certain length
  * @length optional; the length of the generated string 
  * (defaults to 8)
  * 
  * TODO: Replace with something that encodes more characters? a-zA-Z0-9 would be nice…
  *
  * @result a string with random characters
  */
function randstr($length = 8) {
	$length = ($length < 4) ? 4 : $length;
	return bin2hex(random_bytes(($length-($length%2))/2));
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

// almost dead: only used in framework/member.php:member_full_name
function optional($value)
{
	return strlen($value) > 0 ? ' ' . $value : '';
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

/**
  * Parse an email message and substitute variables and constants. The 
  * function will first look for email in public/email and will
  * fallback to the default theme if the file could not be found
  * @param string the name of the email file to parse
  * @param array the data to substitute
  *
  * @return string A string with substituted data and constants
  */
function parse_email($email, $data)
{
	if (file_exists('public/email/' . $email))
		$contents = file_get_contents('public/email/' . $email);
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
	$path = 'public/email/' . $file;

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
	if (substr($file, 0, 1) === '/')
		$path = $file;
	else
		$path = '/' . $file;

	$abs_path = realpath($_SERVER["DOCUMENT_ROOT"]) . $path;

	if ($include_filemtime && file_exists($abs_path))
		$path .= '?' . filemtime($abs_path);

	return $path;
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

// almost dead: only used in View:byName
function find_file(array $search_paths)
{
	foreach ($search_paths as $path)
		if (file_exists($path))
			return $path;

	return null;
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

	$options = [
		'expires' => $cookie_time,
		'path' => '/',
		'domain' => $domain, 
		'httponly' => true,
	];

	if (!empty($_SERVER['HTTPS'])) {
		$options['secure'] = true;
		$options['samesite'] = 'None';
	}

	setcookie($name, $value, $options);

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

function curry_call_method($method, $arguments = [])
{
	$arguments = func_get_args();
	array_shift($arguments);
	
	return function($object) use ($method, $arguments) {
		return call_user_func_array([$object, $method], $arguments);
	};
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
	// Alternative sendmail implementations may not like this "oi" flag, which could result in a broken pipe. Remove temporarily if you're running into issues on your test environment.
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

function array_find(array $elements, callable $test)
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

// Dead after old form stuff is gone
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
	$resize_exts = get_config_value('filemanager_image_resize_extensions', ['jpg', 'jpeg', 'png']);
	if (!$width || !in_array(pathinfo($path, PATHINFO_EXTENSION), $resize_exts))
		return sprintf('%s/%s', $filemanager_root, $path);
	return sprintf('%s/images/resize?f=%s&w=%d', $filemanager_root, urlencode($path), $width);
}
