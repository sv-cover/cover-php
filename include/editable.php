<?php

	require_once('markup.php');
	require_once('login.php');
	require_once('member.php');

	if (!defined('IN_SITE'))
		return;

	function _editable_parse_commissie_leden(&$page, $owner) {
		if (strstr($page, '[commissie_leden]'))
			$page = str_ireplace('[commissie_leden]', '', $page);
	}
	
	function _editable_parse_commissie_poll(&$page, $owner) {
		if (!strstr($page, '[commissie_poll]'))
			return;

		/* Commissie polls are deprecated */
		$page = str_ireplace('[commissie_poll]', '', $page);
	}
	
	function _editable_parse_commissie_email(&$page, $owner) {
		if (!strstr($page, '[commissie_email]'))
			return;

		$model = get_model('DataModelCommissie');
			
		$email = $model->get_email($owner);
		$page = str_replace('[commissie_email]', '<a href="mailto:' . rawurlencode($email) . '">E-Mail</a>', $page);
	}
	
	function _editable_parse_commissie_foto(&$page, $owner) {
		if (strstr($page, '[commissie_foto]'))
			$page = str_ireplace('[commissie_foto]', '', $page);
	}

	function _editable_parse_commissie_agenda(&$page, $owner) {
		if (strstr($page, '[commissie_agenda]'))
			$page = str_ireplace('[commissie_agenda]', '', $page);
	}
	
	function _editable_parse_commissie_prive(&$page, $owner) {
		if (member_in_commissie($owner))
			$page = preg_replace(Array('/\[prive\]/i','/\[\/prive\]/i'), '', $page);
		else
			$page = preg_replace('/\[prive\](.*?)\[\/prive\]/ism', '', $page);
			
	}
	
	function _editable_parse_commissie_summary(&$page, $owner) {
		/* Just remove because the summary isn't used in general view */
		$page = preg_replace('/\[samenvatting\](.+?)\[\/samenvatting\]\s*/ism', '', $page);
	}

	function _editable_parse_commissie_header(&$page, $owner) {
		/* Just remove because the header isn't used in general view */
		$page = preg_replace('/\[h1\](.+?)\[\/h1\]\s*/ism', '', $page);
	}

	/** @group Editable
	  * Parse editable page and return an array of pages with all markup
	  * formatted in html
	  * @content the editable page content
	  * @owner the owner of the editable page
	  *
	  * @result an array of pages with all markup replaced by html
	  */
	function editable_parse($page, $owner) {
		_editable_parse_commissie_header($page, $owner);
		_editable_parse_commissie_summary($page, $owner);

		$page = markup_parse($page);

		_editable_parse_commissie_poll($page, $owner);
		_editable_parse_commissie_leden($page, $owner);
		_editable_parse_commissie_email($page, $owner);
		_editable_parse_commissie_foto($page, $owner);
		_editable_parse_commissie_agenda($page, $owner);
		
		_editable_parse_commissie_prive($page, $owner);
		
		return markup_clean($page);
	}
	
	/** @group Editable
	  * Get the summary of the editable
	  * @content the editable page content
	  * @owner the owner of the editable page
	  *
	  * @result a string containing the summary or an empty string if
	  * no summary could be found
	  */
	function editable_get_summary($content, $owner)
	{
		return preg_match('/\[samenvatting\](.+?)\[\/samenvatting\]/msi', $content, $matches)
			? editable_parse($matches[1], $owner) : '';
	}
