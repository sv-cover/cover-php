<?php
if (!defined('IN_SITE'))
	return;

require_once 'src/framework/config.php';
require_once 'src/framework/streams.php';
require_once 'src/framework/gettext.php';

/** @group i18n
  * A gettext noop function. This will just return the message. It's used
  * to be able to mark the message as a translatable string (by using
  * gettext tools) but not actually translate it yet. A use case would be
  * to only translate the message in certain circumstances.
  * @message The message
  *
  * @result the same unaltered message
  */
function N__($message) {
	return $message;
}

/** @group i18n
  * Initialize the internationalization stuff
  *
  */
function init_i18n() {
	i18n_translation::set_path(dirname(__FILE__) . '/../locale');
	i18n_translation::set_locale(i18n_get_locale());
	
	/* Set language to use */
	putenv('LANG='.i18n_get_locale().'.UTF-8');
	setlocale(LC_ALL, i18n_get_locale().'.UTF-8');
}

class i18n_translation
{
	static private $root;

	static private $reader;

	static private $locale;

	static public function get_reader() {
		if (!self::$reader)
			self::$reader = self::init_reader();

		return self::$reader;
	}

	static public function set_path($path) {
		self::$root = $path;
		self::$reader = null;
	}

	static public function set_locale($locale) {
		self::$locale = $locale;
		self::$reader = null;
	}

	static private function init_reader() {
		$translation = sprintf('%s/%s/LC_MESSAGES/cover-web.mo', self::$root, self::$locale);

		$stream = file_exists($translation)
			? new FileReader($translation)
			: null;

		return new gettext_reader($stream);
	}
}

function __($message_id) {
	if ($message_id == '')
		return '';

	return i18n_translation::get_reader()->translate($message_id);
}

function __translate_parts($message, $separators = ',') {
	$pattern = '/(\s*[' . preg_quote($separators, '/') . ']+\s*)/';
	$parts = preg_split($pattern, $message, -1, PREG_SPLIT_DELIM_CAPTURE);

	foreach ($parts as &$part)
		if (!preg_match($pattern, $part))
			$part = __($part);

	return implode('', $parts);
}

/**
 * Give a number the correct suffix. E.g. 1, 2, 3 will become 1st, 2nd and
 * 3th, depending on the locale returned bij i18n_get_locale().
 *
 * @param int $n the number
 * @return string number with suffix.
 */
function ordinal($n) {
	switch (i18n_get_locale())
	{
		case 'nl_NL':
			return sprintf('%de', $n);

		case 'en_US':
			if ($n >= 11 && $n <= 13)
				return sprintf('%dth', $n);
			elseif ($n % 10 == 1)
				return sprintf('%dst', $n);
			elseif ($n % 10 == 2)
				return sprintf('%dnd', $n);
			elseif ($n % 10 == 3)
				return sprintf('%drd', $n);
			else
				return sprintf('%dth', $n);

		default:
			return sprintf('%d', $n);
	}
}

function _ngettext($singular, $plural, $number) {
	return i18n_translation::get_reader()->ngettext($singular, $plural, $number);
}

function __N($singular, $plural, $number) {
	return sprintf(_ngettext($singular, $plural, $number), $number);
}

/** @group i18n
  * Get the current locale
  *
  * @result the current locale
  */
function i18n_get_locale() {
	// Bypass logic, the website should be English only now
	return 'en_US';

	if (get_auth()->logged_in())
		$language = get_identity()->get('taal');
	elseif (isset($_SESSION['taal']))
		$language = $_SESSION['taal'];
	else
		$language = http_get_preferred_language();
	
	if (!isset($language) || !i18n_valid_language($language))
		$language = get_config_value('default_language', 'en');
	
	$locales = _i18n_locale_map();
	
	return $locales[$language];
}

function _i18n_locale_map() {
	return array_flip(_i18n_language_map());
}

function _i18n_language_map() {
	return array(
		'nl_NL' => 'nl',
		'en_US' => 'en');
}

/** @group i18n
  * Get all supported languages
  *
  * @result an associative array of support languages
  */
function i18n_get_languages() {
	static $languages = null;
	
	if ($languages !== null)
		return $languages;
	
	$languages = array(
		'nl' => 'Nederlands',
		'en' => 'English');
	
	return $languages;
}

/** @group i18n
  * Checks whether a language is valid
  * @language the language to check
  *
  * @result true if the language is valid, false otherwise
  */
function i18n_valid_language($language) {
	$languages = i18n_get_languages();

	return isset($languages[$language]);
}

/** @group i18n
  * Get the current language (defaults to en)
  *
  * @result the current language
  */
function i18n_get_language() {
	static $languages = null;

	if ($languages === null)
		$languages = _i18n_language_map();
	
	$locale = i18n_get_locale();
	
	if (isset($languages[$locale]))
		return $languages[$locale];
	else
		return get_config_value('default_language', 'en');	
}

function http_get_preferred_language($get_sorted_list = false, $accepted_languages = null)
{
	if (empty($accepted_languages))
		if (!empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
			$accepted_languages = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		else
			return null;

	// regex inspired from @GabrielAnderson on http://stackoverflow.com/questions/6038236/http-accept-language
	if (!preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})*)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $accepted_languages, $lang_parse))
		return null;

	$langs = $lang_parse[1];
	$ranks = $lang_parse[4];

	// (create an associative array 'language' => 'preference')
	$lang2pref = array();
	for ($i=0; $i<count($langs); $i++)
		$lang2pref[$langs[$i]] = (float) (!empty($ranks[$i]) ? $ranks[$i] : 1);

		// (comparison function for uksort)
	$compare_language = function ($a, $b) use ($lang2pref) {
		if ($lang2pref[$a] > $lang2pref[$b])
			return -1;
		elseif ($lang2pref[$a] < $lang2pref[$b])
			return 1;
		elseif (strlen($a) > strlen($b))
			return -1;
		elseif (strlen($a) < strlen($b))
			return 1;
		else
			return 0;
	};

	// sort the languages by prefered language and by the most specific region
	uksort($lang2pref, $compare_language);

	if ($get_sorted_list)
		return $lang2pref;

	// return the first value's key
	reset($lang2pref);
	return key($lang2pref);
}
