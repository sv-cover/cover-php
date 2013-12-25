<?php
	if (!defined('IN_SITE'))
		return;

	require_once('config.php');
	require_once('login.php');
	
	define('HAS_GETTEXT', function_exists('_'));

	if (!HAS_GETTEXT)
		include('i18n_dummy.php');

	/** @group i18n
	  * A gettext noop function. This will just return the message. It's used
	  * to be able to mark the message as a translatable string (by using
	  * gettext tools) but not actually translate it yet. A use case would be
	  * to only translate the message in certain circumstances.
	  * @message The message
	  *
	  * @result the same unaltered message
	  */
	function N_($message) {
		return $message;
	}

	/** @group i18n
	  * Initialize the internationalization stuff
	  *
	  */
	function init_i18n() {
		/* Bind the the domain name to the location of the locale files */
		if (HAS_GETTEXT)
		{
			bindtextdomain('cover-web', dirname(__FILE__) . '/../locale');
		
			/* Set the charset to UTF-8 */
			bind_textdomain_codeset('cover-web', 'ISO-8859-15');

			/* Set the domain to use */
			textdomain('cover-web');
		}

		/* Set language to use */
		putenv('LANG='.i18n_get_locale());
		setlocale(LC_ALL, i18n_get_locale());
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
