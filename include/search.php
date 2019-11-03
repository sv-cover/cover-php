<?php

interface SearchProvider
{
	public function search($query, $limit = null);
}

interface SearchResult
{
	public function get_search_relevance();
	
	public function get_search_type();

	public function get_absolute_url();
}

function text_excerpt($text, $keywords, $radius = 30,
	$highlight_format = '<span class="keyword">$1</span>',
	$glue = '<span class="glue">...</span>')
{
	// Convert text to non-utf8 as the word bound do not work with those characters
	$text = utf8_decode($text);

	// Remove newlines and extra spaces from text
	$text = preg_replace('/\s+/m', ' ', $text);

	$escape_keyword = function($keyword) {
		return preg_quote($keyword, '/');
	};

	$keyword_pattern = '/(' . implode('|', array_map($escape_keyword, $keywords)) . ')/i';

	$chunks = array();
	$offset = 0;

	// Find chunks surrounding the keywords
	while (preg_match($keyword_pattern, $text, $matches, PREG_OFFSET_CAPTURE, $offset))
	{
		$chunks[] = array(
			find_word_bound($text, $matches[0][1] - $radius),
			find_word_bound($text, $matches[0][1] + $radius));

		// Continue searching after this match
		$offset = $matches[0][1] + strlen($matches[0][0]);
	}

	// Merge the chunks if they overlap
	for ($i = 1; $i < count($chunks); ++$i)
	{
		// If the end of the previous chunk is past this chunk, merge them.
		if ($chunks[$i - 1][1] > $chunks[$i][0])
		{
			$chunks[$i - 1][1] = $chunks[$i][1];
			array_splice($chunks, $i--, 1);
		}
	}

	// Cut the chunks from the text, creating excerpts
	$excerpts = array();

	$keyword_pattern = '/(' . implode('|', array_map($escape_keyword, array_map('htmlspecialchars', $keywords))) . ')/i';

	foreach ($chunks as $chunk)
	{
		$excerpt = htmlspecialchars(substr($text, $chunk[0], $chunk[1] - $chunk[0] - 1));

		// Highlight keywords
		$excerpts[] = preg_replace($keyword_pattern, $highlight_format, $excerpt);
	}

	return utf8_encode(implode($glue, $excerpts));
}

function find_word_bound($text, $cursor)
{
	if (preg_match('/(\b\w)/', $text, $match, PREG_OFFSET_CAPTURE, $cursor))
		return $match[0][1];

	return $cursor;
}

function parse_search_query($query)
{
	return preg_split('/\s+/', trim($query));
}

function normalize_search_rank($rank)
{
	$relevance = floatval($rank);

	return $relevance / ($relevance + 1);
}
