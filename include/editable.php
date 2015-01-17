<?php
	if (!defined('IN_SITE'))
		return;

	require_once 'include/markup.php';
	require_once 'include/member.php';
	
	function _editable_parse_commissie_prive(&$page, $owner) {
		if (member_in_commissie($owner))
			$page = preg_replace(array('/\[prive\]/i','/\[\/prive\]/i'), '', $page);
		else
			$page = preg_replace('/\[prive\].*?\[\/prive\]/ism', '', $page);
			
	}
	
	function _editable_parse_commissie_summary(&$page, $owner) {
		/* Just remove because the summary isn't used in general view */
		$page = preg_replace('/\[samenvatting\](.+?)\[\/samenvatting\]\s*/ism', '', $page);
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
		$placeholders = array();

		_editable_parse_commissie_summary($page, $owner);

		_editable_parse_commissie_prive($page, $owner);
		
		$page = markup_parse($page);

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
