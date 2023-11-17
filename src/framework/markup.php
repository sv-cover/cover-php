<?php
if (!defined('IN_SITE'))
	return;

require_once 'src/framework/router.php';

use App\Controller\MailingListsController;

const EXCLUDE_ANCHORS = 1;


function str_replace_once($search, $replace, $subject)
{
	$pos = strpos($subject, $search);

	if ($pos === false)
		return $subject;

	return substr_replace($subject, $replace, $pos, strlen($search));
}

function _markup_parse_code_real($code)
{
	$code = htmlspecialchars($code, ENT_NOQUOTES);
	$code = str_replace("\n", '<br/>', $code);

	while (preg_match('/ ( +?)/', $code, $matches)) {
		$sp = "";
		$sp = str_pad($sp, strlen($matches[0]) * 6, '&nbsp;');
		$code = preg_replace('/ ( +?)/', $sp, $code, 1);
	}

	return '<pre class="code" title="Code">' . $code . '</pre>';
}

function _markup_parse_code(&$markup, &$placeholders)
{
	$count = 0;

	while (preg_match("/( *?\[code(=(.+?))?\](.*?)\[\/code\])/is", $markup, $match)) {
		$placeholder = sprintf('#CODE%d#', $count++);
		$placeholders[$placeholder] = _markup_parse_code_real($match[4]);
		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_links(&$markup, $header_offset, &$placeholders, $flags)
{
	$count = 0;

	while (preg_match('/\[url=(.*?)\](.*?)\[\/url\]/is', $markup, $match)) {
		$placeholder = sprintf('#LINK%d#', $count++);

		$host = parse_url($match[1], PHP_URL_HOST);

		$target = $host !== null && $host != parse_url(ROOT_DIR_URI, PHP_URL_HOST) ? ' target="_blank"' : '';

		$placeholders[$placeholder] = '<a rel="nofollow"' . $target . ' href="' . $match[1] . '">' . markup_parse($match[2], $header_offset, $placeholders, $flags | EXCLUDE_ANCHORS) . '</a>';

		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_urls(&$markup, &$placeholders)
{
	$linkcount = 0;

	while (preg_match("/((([A-Za-z]{3,9}:(?:\/\/)?)[A-Za-z0-9.-]+|(?:www.)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w\-_]*)?\??(?:[\-\+=&;%@.\w_]*)#?(?:[\w]*))?)/i", $markup, $match)) {
		$url = preg_match('~^https?://~', $match[0]) ? $match[0] : 'http://' . $match[0];

		$placeholder = sprintf('#URL%d#', $linkcount++);

		$host = parse_url($match[0], PHP_URL_HOST);

		$is_external = $host !== null && $host != parse_url(ROOT_DIR_URI, PHP_URL_HOST);
		$rel = $is_external ? ' rel="noopener noreferrer nofollow"' : ' rel="nofollow"';
		$target = $is_external ? ' target="_blank"' : '';

		$placeholders[$placeholder] = '<a' . $target . $rel . ' href="' . $url . '">' . (strlen($match[0]) > 60 ? (substr($match[0], 0, 28) . '...' . substr($match[0], -29)) : $match[0]) . '</a>';

		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_quotes_real($matches)
{
	if (substr($matches[3], 0, 2) == "\n") {
		$matches[3] = substr($matches[3], 2);
	}
	if ($matches[2])
		return '<div class="quote" title="quote"><span class="author">' . $matches[2] . '</span>: ' . $matches[3];
	else
		return '<div class="quote" title="quote"><br />' . $matches[3];
}

function _markup_parse_quotesend_real($matches)
{
	return '</div>';
}


function _markup_parse_quotes(&$markup)
{

	$markup = preg_replace_callback('/\[quote(=([^\]]+))?\](.*?)/ims', '_markup_parse_quotes_real', $markup);
	$markup = preg_replace_callback('/\[\/quote\]/ims', '_markup_parse_quotesend_real', $markup);

}


function _markup_prepare_table_row($match, &$maxcol)
{
	$col = substr_count($match, '||') + 1;

	$maxcol = max($col, $maxcol);
}

function _markup_parse_table_row($match, $maxcol)
{
	if ($match == '')
		return "";

	$col = substr_count($match, '||') + 1;

	if ($col < $maxcol)
		$colspan = ' colspan="' . (($maxcol - $col) + 1) . '"';
	else
		$colspan = '';

	return '<tr><td' . $colspan . '>' . str_replace('||', '</td><td>', $match) . '</td></tr>';
}

function _markup_parse_table_real($matches)
{
	$class = $matches[2];
	$contents = trim($matches[3]);
	$result = '';

	if (!$class)
		$class = 'table';
	else
		$class = 'table ' . $class;

	$result = '<table class="' . $class . '">';

	if (preg_match_all('/^\s*\|\|(.*?)\|\|\s*$/smu', $contents, $matches)) {
		$maxcol = 0;

		foreach ($matches[1] as $match)
			_markup_prepare_table_row($match, $maxcol);

		foreach ($matches[1] as $match)
			$result .= _markup_parse_table_row($match, $maxcol);
	} else {
		$result .= sprintf('<!-- cannot parse table %s -->', $contents);
	}

	return $result . '</table>';
}

function _markup_parse_table(&$markup)
{
	$markup = preg_replace_callback('/\[table( ([a-z-]+))?\](.*?)\[\/table\]/is', '_markup_parse_table_real', $markup);
}

function _markup_parse_spaces(&$markup)
{
	while (preg_match('/ ( +?)/', $markup, $matches)) {
		$sp = "";
		$sp = str_pad($sp, strlen($matches[0]) * 6, '&nbsp;');
		$markup = preg_replace('/ ( +?)/', $sp, $markup, 1);
	}
}

function _markup_parse_simple(&$markup)
{
	// TODO: Replace this beast with something that has a stack!
	$tags = array('[i]', '[/i]', '[b]', '[/b]', '[u]', '[/u]', '[s]', '[/s]', '[ol]', '[/ol]', '[ul]', '[/ul]', '[li]', '[/li]', '[center]', '[/center]', '[hl]', '[/hl]', '[small]', '[/small]');
	$replace = array('<i>', '</i>', '<b>', '</b>', '<u>', '</u>', '<s>', '</s>', '<ol>', '</ol>', '<ul>', '</ul>', '<li>', '</li>', '<div class="text_center">', '</div>', '<span class="highlight">', '</span>', '<small>', '</small>');

	$markup = str_replace($tags, $replace, $markup);
}

function _markup_parse_images(&$markup, &$placeholders)
{
	static $count = 0;

	while (preg_match('/\[img(?P<classes>(\.[a-z-]+)*)=(?P<url>.+?)\]/', $markup, $match)) {
		$placeholder = sprintf('#IMAGE%d#', $count++);
		$placeholders[$placeholder] = sprintf('<img class="%s" src="%s">',
			str_replace('.', ' ', $match['classes']),
			markup_format_attribute($match['url']));
		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_fontawesome(&$markup, &$placeholders)
{
	static $count = 0;

	while (preg_match('/\[fontawesome\s+icon="([^"]+)"\s*(?:label="([^"]+)")?\]/', $markup, $match)) {
		$placeholder = sprintf('#FONTAWESOME%d#', $count++);
		$label = $match[2] ?? null;
		if($label){
			$label_info = 'aria-label="'.$label.'"';
		} else {
			$label_info = 'aria-hidden="true"';
		}

		$placeholders[$placeholder] = '<i class="fa ' . $match[1] . '" ' . $label_info . '></i>';
		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_member(&$markup, &$placeholders)
{
	static $count = 0;

	while (preg_match('/\[member\s+name="([^"]+)"\s*(?:position="([^"]+)")?\s*(?:image="([^"]+)")?\](.*?)\[\/member\]/s', $markup, $match)) {

		$placeholder = sprintf('#MEMBER%d#', $count++);

		$name = $match[1];
		$position = $match[2] ?? null;
		$image = $match[3] ?? null;
		$content = markup_parse($match[4]);

		$html = '<div class="member-block">';
			$html .= '<div class="cover-thumbnail">';
				$html .= '<div class="overlay is-bottom">';
					$html .= '<div class="name boxed-title-wrapper">';
						$html .= '<h3 class="boxed-title is-size-5-mobile">';
							$html .= $name;
						$html .= '</h3>';
					$html .= '</div>';
					if($position) {
						$html .= '<div class="boxed-title-wrapper">';
							$html .= '<span class="boxed-title has-text-weight-normal is-size-6">';
								$html .= '<i>' . $position . '</i>';
							$html .= '</span>';
						$html .= '</div>';
					}
				$html .= '</div>';
				$html .= '<figure class="image">';
					if($image) {
						$html .= '<img src="' . $image . '" alt="Picture of '.$name.'" />';
					}
				$html .= '</figure>';
			$html .= '</div>';
			$html .= '<div class="member-block-details column">';
				$html .= '<p>';
					$html .= $content;
				$html .= '</p>';
			$html .= '</div>';
		$html .= '</div>';

		$placeholders[$placeholder] = $html;

		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_youtube(&$markup, &$placeholders)
{
	static $count = 0;

	while (preg_match('/\[youtube=(.+?)\]/', $markup, $match)) {
		$placeholder = sprintf('#YOUTUBE%d#', $count++);
		$placeholders[$placeholder] = '<figure class="image is-16by9 youtube"><iframe class="has-ratio" src="https://www.youtube-nocookie.com/embed/' . $match[1] . '" frameborder="0" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></figure>';
		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_video(&$markup, &$placeholders)
{
	static $count = 0;

	while (preg_match('/\[video=(.+?)\]/', $markup, $match)) {
		if (!filter_var($match[1], FILTER_VALIDATE_URL))
			return $match[0];

		$placeholder = sprintf('#VIDEO%d#', $count++);
		$placeholders[$placeholder] = '<video class="markup-video" src="' . $match[1] . '" controls><a href="' . $match[1] . '">Watch video</a></video>';
		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_header(&$markup, $header_offset = 0)
{
	$markup = preg_replace_callback(
		'/\[h(?P<level>\d)(?<classes>(\.[a-z-])*)\](?P<content>.+?)\[\/h\\1\]\s*/is',
		function($match) use ($header_offset) {
			return sprintf('<h%d class="%s">%s</h%1$d>',
				max(min(intval($match['level']) + $header_offset, 6), 1),
				str_replace('.', ' ', $match['classes']),
				$match['content']);
		}, $markup);
}

function _markup_parse_placeholders(&$markup, $placeholders)
{
	foreach ($placeholders as $placeholder => $content)
		$markup = str_replace_once($placeholder, $content, $markup);
}

function _markup_process_macro_commissie($commissie)
{
	static $model = null;

	if ($model === null)
		$model = get_model('DataModelCommissie');

	try {
		$iter = $model->get_from_name($commissie);

		$router = get_router();

		return '<a href="' . $router->generate('page', ['id' => $iter->get('page_id')]) . '">' . markup_format_text($iter->get('naam')) . '</a>';
	} catch (DataIterNotFoundException $e) {
		return '';
	}
}

function _markup_parse_macro_real($matches)
{
	if (!function_exists('_markup_process_macro_' . $matches[1]))
		return $matches[0];

	return call_user_func('_markup_process_macro_' . $matches[1], $matches[2]);
}

function _markup_parse_macros(&$markup)
{
	$markup = preg_replace_callback('/\[\[([a-z_]+)\((.*?)\)\]\]/', '_markup_parse_macro_real', $markup);
}

function _markup_parse_emails(&$markup, &$placeholders)
{
	$count = 0;

	while (preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $markup, $match)) {
		$placeholder = sprintf('#EMAIL%d#', $count++);
		$placeholders[$placeholder] = sprintf('<a rel="nofollow" href="mailto:%s">%s</a>',
			rawurlencode($match[0]), markup_format_text($match[0]));

		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_mailinglist(&$markup, &$placeholders)
{
	// Find [mailinglist]email/id[/mailinglist] placeholders
	// and replace them by clickable stuff.

	$count = 0;

	while (preg_match('/\[mailinglist\]([^\[]+)\[\/mailinglist\]/i', $markup, $match)) {
		try {
			$controller = new MailingListsController();
			$content = $controller->run_embedded($match[1]);
		} catch (Exception $e) {
			sentry_report_exception($e);
			$content = sprintf('<pre>%s</pre>', $e->getMessage());
		}

		$placeholder = sprintf('#MAILINGLIST%d#', $count++);
		$placeholders[$placeholder] = $content;

		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

function _markup_parse_membersonly(&$markup, &$placeholders)
{
	// Find [membersonly]email/id[/membersonly] placeholders and replace
	// them by content or a login cta.

	$count = 0;

	while (preg_match('/\[membersonly(=(?P<title>[^\]]+))?\](?P<content>.+?)\[\/membersonly\]/is', $markup, $match)) {
		if (get_auth()->logged_in())
			$content = markup_parse($match['content']);
		else
			$content = sprintf('<p>This content is only visible for members.</p><a href="sessions.php?view=login&amp;referrer=%s" class="button is-primary">%s</a>', urlencode($_SERVER['REQUEST_URI']), __('Log in'));

		$placeholder = sprintf('#MEMBERSONLY%d#', $count++);

		if ($match['title'])
			$placeholders[$placeholder] = sprintf('<div class="box"><h2>%s</h2>%s</div>', $match['title'], $content);
		else
			$placeholders[$placeholder] = sprintf('<div class="box">%s</div>', $content);

		$markup = str_replace_once($match[0], $placeholder, $markup);
	}
}

/** @group Markup
 * Parse markup
 * @markup the markup to parse
 *
 * @result a string with all the markup replaced by html
 */
function markup_parse($markup, $header_offset = 0, &$placeholders = null, $flags = 0)
{
	if (empty($markup))
		return '';

	if (!$placeholders)
		$placeholders = array();

	/* Just remove because the header isn't used in general view */
	$markup = preg_replace('/\[h1\](.+?)\[\/h1\]\s*/ism', '', $markup);

	/* Just remove because the summary isn't used in general view */
	$markup = preg_replace('/\[samenvatting\](.+?)\[\/samenvatting\]\s*/ism', '', $markup);

	$markup = preg_replace('/\[prive\].*?\[\/prive\]/ism', '', $markup);

	/* Filter code tags */
	_markup_parse_code($markup, $placeholders);

	/* Replace [mailinglist] embed */
	_markup_parse_mailinglist($markup, $placeholders);

	/* Replace [membersonly] */
	_markup_parse_membersonly($markup, $placeholders);

	/* Parse [img=], [youtube=] [video=] */
	_markup_parse_images($markup, $placeholders);

	_markup_parse_youtube($markup, $placeholders);

	_markup_parse_video($markup, $placeholders);

	_markup_parse_member($markup, $placeholders);

	_markup_parse_fontawesome($markup, $placeholders);

	/* Filter [url] */
	if (!($flags & EXCLUDE_ANCHORS))
		_markup_parse_links($markup, $header_offset, $placeholders, $flags);

	/* Replace scary stuff and re-replace not so very scary stuff */
	$markup = htmlspecialchars($markup, ENT_NOQUOTES);
	$markup = str_replace('&amp;', '&', $markup);

	/* Parse quotes */
	_markup_parse_quotes($markup);

	/* Parse tables */
	_markup_parse_table($markup);

	/* Parse spaces */
	_markup_parse_spaces($markup);

	/* Parse bare e-mails and urls */
	if (!($flags & EXCLUDE_ANCHORS)) {
		_markup_parse_emails($markup, $placeholders);
		_markup_parse_urls($markup, $placeholders);
	}

	/* Parse simple tags */
	_markup_parse_simple($markup);

	/* Parse header */
	_markup_parse_header($markup, $header_offset);

	/* Parse macros */
	_markup_parse_macros($markup);

	$markup = str_replace("\n", "<br/>\n", $markup);
	$markup = str_replace('$', '&#36;', $markup);
	$markup = str_replace('\\', '&#92;', $markup);
	$markup = str_replace('{', '&#123;', $markup);

	$markup = markup_clean($markup);
	/* CHECK: this is bad! */
	/* $markup .= '</I></B></U></S></UL></LI>';*/

	/* Put codes and links back */
	_markup_parse_placeholders($markup, $placeholders);

	return $markup;
}

/**
 * @group Markup
 * Remove bb-code from a text
 *
 * @markup text with bb-code
 * @result text stripped from bb-code
 */
function markup_strip($markup)
{
	return preg_replace('/\[[^\[\]\s]*\]/', '', $markup);
}

/** @group Markup
 * Clear markup from redundant br tags
 * @text the string to clean up
 *
 * @result the cleaned up string
 */
function markup_clean($text)
{
	return preg_replace('/(\/(li|div|ul|ol|h[0-9]+)[^>]*>)\s*<br\/?>/im', '$1', $text);
}

/** @group Markup
 * Format to be used in for example a textarea. This function
 * strips slashes and replaces htmlentities
 * @text the text to be formatted
 *
 * @result the formatted text
 */
function markup_format_text($text)
{
	$text = htmlspecialchars($text, ENT_COMPAT, WEBSITE_ENCODING);

	/*$text = str_replace('&','&amp;',$str);
	$text = str_replace('"','&quot;',$str);
	$text = str_replace('<','&lt;',$str);
	$text = str_replace('>','&gt;',$str);*/

	return $text;
}

function markup_format_attribute($text)
{
	return htmlspecialchars($text, ENT_QUOTES, WEBSITE_ENCODING);
}