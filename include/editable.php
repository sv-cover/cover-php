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

		
		$page = markup_parse($page);

		return markup_clean($page);
	}

