<?php
	if (!defined('IN_SITE'))
		return;

	require_once 'config.php';
	require_once 'login.php';
	require_once 'streams.php';
	require_once 'gettext.php';
	
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
		putenv('LANG='.i18n_get_locale());
		setlocale(LC_ALL, i18n_get_locale());
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
		return i18n_translation::get_reader()->translate($message_id);
	}

	function _ngettext($singular, $plural, $number) {
		return i18n_translation::get_reader()->ngettext($singular, $plural, $number);
	}
	
	/** @group i18n
	  * Get the current locale
	  *
	  * @result the current locale
	  */
	function i18n_get_locale() {
		/* TODO: make this member configurable. 
		   Default to global locale for now */
		$member_data = logged_in();
		$language = 'nl';
	
		if ($member_data)
			$language = $member_data['taal'];
		elseif (isset($_SESSION['taal']))
			$language = $_SESSION['taal'];
		else
			$language = 'nl';
		
		if (!i18n_valid_language($language))
			$language = 'nl';
		
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
	  * Get the current language (defaults to nl)
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
			return 'nl';
		
	}
?>
