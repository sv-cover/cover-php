<?php
	require_once 'include/markup.php';
	
	/** @group Editable
	  * Parse editable page and return an array of pages with all markup
	  * formatted in html
	  * @content the editable page content
	  *
	  * @result an array of pages with all markup replaced by html
	  */
	function editable_parse($page)
	{
		$placeholders = array();

		/* Just remove because the header isn't used in general view */
		$page = preg_replace('/\[h1\](.+?)\[\/h1\]\s*/ism', '', $page);

		/* Just remove because the summary isn't used in general view */
		$page = preg_replace('/\[samenvatting\](.+?)\[\/samenvatting\]\s*/ism', '', $page);

		$page = preg_replace('/\[prive\].*?\[\/prive\]/ism', '', $page);
		
		$page = markup_parse($page);

		return markup_clean($page);
	}

