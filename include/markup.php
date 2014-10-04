<?php
	if (!defined('IN_SITE'))
		return;

	require_once('smileys.php');

	function str_replace_once($search, $replace, $subject)
	{
		$pos = strpos($subject, $search);

		if ($pos === false)
			return $subject;
		
		return substr_replace($subject, $replace, $pos, strlen($search));
	}

	function _markup_parse_code_real($code) {
		$code = htmlspecialchars($code, ENT_NOQUOTES);
		$code = str_replace("\n", '<br/>', $code);

		while (preg_match('/ ( +?)/', $code, $matches)) {
			$sp = "";
			$sp = str_pad($sp, strlen($matches[0]) * 6,'&nbsp;');
			$code = preg_replace('/ ( +?)/', $sp, $code, 1);
		}

		return '<div class="code" title="Code">' . $code . '</div>';
	}

	function _markup_parse_code(&$markup, &$placeholders)
	{
		$count = 0;
		
		while (preg_match("/( *?\[code(=(.+?))?\](.*?)\[\/code\])/is", $markup, $match))
		{
			$placeholder = sprintf('#CODE%d#', $count++);
			$placeholders[$placeholder] = _markup_parse_code_real($match[4]);
			$markup = str_replace_once($match[0], $placeholder, $markup);
		}
	}
	
	function _markup_parse_links(&$markup, &$placeholders)
	{
		$count = 0;

		while (preg_match('/\[url=(.*?)\](.*?)\[\/url\]/is', $markup, $match))
		{
			$placeholder = sprintf('#LINK%d#', $count++);
			$placeholders[$placeholder] = '<a rel="nofollow" href="' . $match[1] . '"' . (strpos($match[1], 'http://') !== FALSE ? '>' : '>') . markup_parse($match[2], $placeholders) . '</a>';
			
			$markup = str_replace_once($match[0], $placeholder, $markup);
		}
	}

	function _markup_parse_urls(&$markup, &$placeholders)
	{
		$linkcount = 0;

		while (preg_match("/((([A-Za-z]{3,9}:(?:\/\/)?)[A-Za-z0-9.-]+|(?:www.)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)/i", $markup, $match))
		{
			$url = preg_match('~^https?://~', $match[0]) ? $match[0] : 'http://' . $match[0];
			
			$placeholder = sprintf('#URL%d#', $linkcount++);
			$placeholders[$placeholder] = '<a rel="nofollow" href="' . $url . '">' . (strlen($match[0]) > 60 ? (substr($match[0], 0, 28) . '...' . substr($match[0], -29)) : $match[0]) . '</a>';

			$markup = str_replace_once($match[0], $placeholder, $markup);
		}
	}
	
	function _markup_parse_quotes_real($matches) {
		if (substr($matches[3], 0, 2) == "\n"){
			$matches[3] = substr($matches[3], 2);
		}
		if ($matches[2])
			return '<div class="quote" title="quote"><span class="author">' . $matches[2] . '</span>: ' . $matches[3];
		else
			return '<div class="quote" title="quote"><br />' . $matches[3];
	}
	function _markup_parse_quotesend_real($matches) {
			return '</div>';
	}
	
	
	function _markup_parse_quotes(&$markup) {
	
		$markup = preg_replace_callback('/\[quote(=([^\]]+))?\](.*?)/ims', '_markup_parse_quotes_real', $markup);
		$markup = preg_replace_callback('/\[\/quote\]/ims', '_markup_parse_quotesend_real', $markup);
		
	}
	
	
	
	function _markup_prepare_table_row($match, &$maxcol) {
		$col = substr_count($match, '||') + 1;
		
		$maxcol = max($col, $maxcol);
	}
	
	function _markup_parse_table_row($match, $maxcol) {
		if ($match == '')
			return "";

		$col = substr_count($match, '||') + 1;

		if ($col < $maxcol)
			$colspan = ' colspan="' . (($maxcol - $col) + 1) . '"';
		else
			$colspan = '';

		return '<tr><td' . $colspan . '>' . str_replace('||', '</td><td>', $match) . '</td></tr>';
	}
	
	function _markup_parse_table_real($matches) {
		$class = $matches[2];
		$contents = $matches[3];
		$result = '';

		if (!$class)
			$class = 'markup_default';
		else
			$class = 'markup_' . $class;
		
		$result = '<table class="' . $class . '">';
		
		if (preg_match_all('/^\|\|(.*?)\|\|$/ims', $contents, $matches)) {
			$maxcol = 0;

			foreach ($matches[1] as $match)
				_markup_prepare_table_row($match, $maxcol);

			foreach ($matches[1] as $match)
				$result .= _markup_parse_table_row($match, $maxcol);		
		}
		
		return $result . '</table>';
	}
	
	function _markup_parse_table(&$markup) {
		$markup = preg_replace_callback('/\[table( ([a-z]+))?\](.*?)\[\/table\]/ims', '_markup_parse_table_real', $markup);
	}
	
	function _markup_parse_spaces(&$markup) {
		while (preg_match('/ ( +?)/', $markup, $matches)) {
			$sp = "";
			$sp = str_pad($sp, strlen($matches[0]) * 6, '&nbsp;');
			$markup = preg_replace('/ ( +?)/', $sp, $markup, 1);
		}
	}
	
	function _markup_parse_smileys(&$markup) {
		$smileys_path = 'themes/' . get_theme() . '/images/smileys';
		
		if (!file_exists(ROOT_DIR_PATH . $smileys_path))
			$smileys_path = 'themes/default/images/smileys';
		
		$markup = trim($markup);
		$smileys = get_smileys();
		
		foreach ($smileys as $code => $image)
			$markup = preg_replace('/' . $code . '/i', '<img src="' . $smileys_path . '/' . $image . '" alt="' . $image . '"/>', $markup);
	}

	function _markup_parse_simple(&$markup) {
		$tags = Array('[i]', '[/i]', '[b]', '[/b]', '[u]', '[/u]', '[s]', '[/s]', '[ol]', '[/ol]', '[ul]', '[/ul]', '[li]', '[/li]', '[center]', '[/center]', '[hl]', '[/hl]');
		$replace = Array('<i>', '</i>', '<b>', '</b>', '<u>', '</u>', '<s>', '</s>', '<ol>', '</ol>', '<ul>', '</ul>', '<li>', '</li>', '<div class="text_center">', '</div>', '<span class="highlight">', '</span>');
		
		$markup = str_replace($tags, $replace, $markup);
	}
	
	function _markup_parse_images(&$markup, &$placeholders)
	{
		$count = 0;

		while (preg_match('/\[img=(.+?)\]/', $markup, $match))
		{
			$placeholder = sprintf('#IMAGE%d#', $count++);
			$placeholders[$placeholder] = '<img src="' . htmlentities($match[1], ENT_QUOTES) . '" style="max-width: 100%;">';
			$markup = str_replace_once($match[0], $placeholder, $markup);
		}
	}
	function _markup_parse_youtube(&$markup, &$placeholders)
	{
		$count = 0;

		while (preg_match('/\[youtube=(.+?)\]/', $markup, $match))
		{
			$placeholder = sprintf('#VIDEO%d#', $count++);
			$placeholders[$placeholder] = '<div class="youtube-container"><iframe src="//www.youtube.com/embed/' . $match[1] . '" frameborder="0" allowfullscreen></iframe></div>';
			$markup = str_replace_once($match[0], $placeholder, $markup);
		}
	}
	
	function _markup_parse_header(&$markup) {
		$markup = preg_replace('/\[(\/)?h(.+?)\]\s*/ies', '"<$1h$2>"', $markup);
	}

	function _markup_parse_placeholders(&$markup, $placeholders)
	{
		foreach ($placeholders as $placeholder => $content)
			$markup = str_replace_once($placeholder, $content, $markup);
	}
	
	function _markup_process_macro_commissie($commissie) {
		static $model = null;
		
		if ($model === null)
			$model = get_model('DataModelCommissie');
		
		$iter = $model->get_from_name($commissie);
		
		if ($iter === null)
			return '';
		
		return '<a href="show.php?id=' . $iter->get('page') . '">' . markup_format_text($iter->get('naam')) . '</a>';
	}
	
	function _markup_parse_macro_real($matches) {
		if (!function_exists('_markup_process_macro_' . $matches[1]))
			return $matches[0];
		
		return call_user_func('_markup_process_macro_' . $matches[1], $matches[2]);
	}
	
	function _markup_parse_macros(&$markup) {
		$markup = preg_replace_callback('/\[\[([a-z_]+)\((.*?)\)\]\]/', '_markup_parse_macro_real', $markup);
	}

	function _markup_parse_emails_real($matches) {
		return sprintf('<a href="mailto:%s">%s</a>',
			rawurlencode($matches[0]),
			markup_format_text($matches[0]));
	}
	
	function markup_parse_emails($markup) {
		return preg_replace_callback('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', '_markup_parse_emails_real', $markup);
	}

	/** @group Markup
	  * Parse markup
	  * @markup the markup to parse
	  *
	  * @result a string with all the markup replaced by html
	  */
	function markup_parse($markup, &$placeholders = null) {
		if (!$placeholders)
			$placeholders = array();
		
		$markup .= "\n";

		/* Filter code tags */
		_markup_parse_code($markup, $placeholders);

		/* Parse [img=] and [youtube=] */
		_markup_parse_images($markup, $placeholders);

		_markup_parse_youtube($markup, $placeholders);
		
		/* Filter [url] */
		_markup_parse_links($markup, $placeholders);

		/* Replace scary stuff and re-replace not so very scary stuff */
		$markup = htmlspecialchars($markup, ENT_NOQUOTES);
		$markup = str_replace('&amp;', '&', $markup);

		/* Parse quotes */
		_markup_parse_quotes($markup);

		/* Parse tables */
		_markup_parse_table($markup);
	
		/* Parse spaces */
		_markup_parse_spaces($markup);
		
		/* Parse bare links */
		_markup_parse_urls($markup, $placeholders);

		/* Parse smileys */
		_markup_parse_smileys($markup);

		/* Put codes and links back */
		_markup_parse_placeholders($markup, $placeholders);

		/* Parse simple tags */
		_markup_parse_simple($markup);

		/* Parse header */
		_markup_parse_header($markup);
		
		/* Parse macros */
		_markup_parse_macros($markup);

		$markup = str_replace("\n", "<br/>\n", $markup);
		$markup = str_replace('$', '&#36;', $markup);
		$markup = str_replace('\\', '&#92;', $markup);
		$markup = str_replace('{', '&#123;', $markup);
		
		$markup = markup_clean($markup);
		/* CHECK: this is bad! */
		/* $markup .= '</I></B></U></S></UL></LI>';*/
	
		return $markup;
	}
	
	/** @group Markup
	  * Clear markup from redundant br tags
	  * @text the string to clean up
	  *
	  * @result the cleaned up string
	  */
	function markup_clean($text) {
		return preg_replace('/(\/(li|div|ul|ol|h[0-9]+)[^>]*>)\s*<br\/?>/im', '$1', $text);
	}
	
	/** @group Markup
	  * Format to be used in for example a textarea. This function 
	  * strips slashes and replaces htmlentities
	  * @text the text to be formatted
	  *
	  * @result the formatted text
	  */
	function markup_format_text($text) {
		$text = htmlspecialchars($text, ENT_COMPAT, WEBSITE_ENCODING);
		
		/*$text = str_replace('&','&amp;',$str);
		$text = str_replace('"','&quot;',$str);
		$text = str_replace('<','&lt;',$str);
		$text = str_replace('>','&gt;',$str);*/

		return $text;
	}

	function markup_format_attribute($text) {
		return htmlspecialchars($text, ENT_QUOTES, WEBSITE_ENCODING);
	}
?>
