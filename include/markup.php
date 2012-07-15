<?php
	if (!defined('IN_SITE'))
		return;

	require_once('smileys.php');

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

	function _markup_parse_begin_code(&$markup, &$codes) {
		$codecount = 0;
		
		while (preg_match("/( *?\[code(=(.+?))?\](.*?)\[\/code\])/is", $markup, $matches) ) {
			$codes[$codecount] = _markup_parse_code_real($matches[4]);

			/* CHECK: The code thingie is weird */
			$markup = preg_replace('/( *?\[code(=(.+?))?\](.*?)\[\/code\])/eis', '" #CODE" . $codecount++ . "#"', $markup, 1);
		}
	}
	
	function _markup_parse_end_code(&$markup, $codes) {
		$markup = preg_replace('/#CODE(.*?)#/e', "\$codes[$1]", $markup);
	}
	
	function _markup_parse_begin_urls(&$markup, &$urls) {
		$linkcount = 0;

		while (preg_match('/\[url=(.*?)\](.*?)\[\/url\]/is', $markup, $matches)) {
			$urls[$linkcount] = '<a rel="nofollow" href="' . $matches[1] . '"' . (strpos($matches[1], 'http://') !== FALSE ? '>' : '>') . $matches[2] . '</a>';

			/* CHECK: the link thingie is weird */
			$markup = preg_replace( '/\[url=(.*?)\](.*?)\[\/url\]/eis','"#LINK".$linkcount++."#"', $markup, 1);
		}

	}
	
	function _markup_parse_end_urls(&$markup, $urls) {
		$markup = preg_replace('/#LINK(.*?)#/e', "\$urls[$1]", $markup);
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
		
		//var_dump($contents);
		
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
	
	function _markup_parse_links_real($matches) {
		$prefix = (!isset($matches[2]) || $matches[2] == "") ? "http://" : "";
		$url = $prefix . $matches[1];
		
		
		return '<a rel="nofollow" href="' . $url . '">' . (strlen($url) > 60 ? (substr($url, 0, 28) . '...' . substr($url, -29)) : $url) . '</a>';
	}
	
	function _markup_parse_links(&$markup) {
		$reg = "((?:(?:(irc|news|telnet|nttp|file|http|sftp|ftp|https|dav|callto):\/\/)|(?:www|ftp)[-A-Za-z0-9]*\.)[-A-Za-z0-9\.@:]+[^]'\.}>\)\s,\/\"\!]+(:[0-9]*)?(\/[-A-Za-z0-9_\$\.\+\!\*\(\),;:@&=\?\/~\#\%]*[^]'\.}>\)\s,\"\!]|\/)?)";

		$markup = preg_replace_callback('/' . $reg . '/i', '_markup_parse_links_real', $markup);
	}
	
	function _markup_parse_spaces(&$markup) {
		while (preg_match('/ ( +?)/', $markup, $matches)) {
			$sp = "";
			$sp = str_pad($sp, strlen($matches[0]) * 6, '&nbsp;');
			$markup = preg_replace('/ ( +?)/', $sp, $markup, 1);
		}
	}
	
	function _markup_parse_smileys(&$markup) {
		$smileys_path = 'themes/' . get_theme . '/images/smileys';
		
		if (!file_exists(ROOT_DIR_PATH . $smileys_path))
			$smileys_path = 'themes/default/images/smileys';
		
		$smileys_path = ROOT_DIR_URI . $smileys_path;

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
	
	function _markup_parse_images(&$markup) {
		$markup = preg_replace('/\[img=(.+?)\]/', '<img src="http://$1" style="max-width: 400px;">', $markup);
	}
	function _markup_parse_youtube(&$markup) {
		$markup = preg_replace('/\[youtube=(.+?)\]/', '<iframe width="420" height="315" src="http://www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe>', $markup);
	}
	
	function _markup_parse_header(&$markup) {
		$markup = preg_replace('/\[(\/)?h(.+?)\]\s*/ies', '"<$1h$2>"', $markup);
	}
	
	function _markup_process_macro_commissie($commissie) {
		static $model = null;
		
		if ($model === null)
			$model = get_model('DataModelCommissie');
		
		$iter = $model->get_from_name($commissie);
		
		if ($iter === null)
			return '';
		
		return '<a href="show.php?id=' . $iter->get('page') . '">' . $iter->get('naam') . '</a>';
	}
	
	function _markup_parse_macro_real($matches) {
		if (!function_exists('_markup_process_macro_' . $matches[1]))
			return $matches[0];
		
		return call_user_func('_markup_process_macro_' . $matches[1], $matches[2]);
	}
	
	function _markup_parse_macros(&$markup) {
		$markup = preg_replace_callback('/\[\[([a-z_]+)\((.*?)\)\]\]/', '_markup_parse_macro_real', $markup);
	}
	
	/** @group Markup
	  * Parse markup
	  * @markup the markup to parse
	  *
	  * @result a string with all the markup replaced by html
	  */
	function markup_parse($markup) {
		$markup .= "\n";

		/* Filter code tags */
		$codes = Array();
		_markup_parse_begin_code($markup, $codes);

		/* Filter urls */
		$urls = Array();
		_markup_parse_begin_urls($markup, $urls);

		/* Replace scary stuff and re-replace not so very scary stuff */
		$markup = htmlspecialchars($markup, ENT_NOQUOTES);
		$markup = str_replace('&amp;', '&', $markup);

		/* Parse links */
		_markup_parse_links($markup);

		/* Parse quotes */
		_markup_parse_quotes($markup);

		/* Parse tables */
		_markup_parse_table($markup);
	
		/* Parse spaces */
		_markup_parse_spaces($markup);
		
		/* Parse smileys */
		_markup_parse_smileys($markup);

		/* Put codes and links back */
		_markup_parse_end_code($markup, $codes);
		_markup_parse_end_urls($markup, $urls);

		/* Parse simple tags */
		_markup_parse_simple($markup);

		/* Parse images */
		_markup_parse_images($markup);
		_markup_parse_youtube($markup);
		
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
		$text = htmlentities($text);
		
		/*$text = str_replace('&','&amp;',$str);
		$text = str_replace('"','&quot;',$str);
		$text = str_replace('<','&lt;',$str);
		$text = str_replace('>','&gt;',$str);*/

		return $text;
	}
?>
