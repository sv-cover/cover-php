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

function split_sentences($text)
{
	return preg_split('/(?<=[.?!;])\s+(?=\p{Lu})/', strip_tags($text), null, PREG_SPLIT_NO_EMPTY);
}

function split_words($sentence)
{
	return preg_match_all('/(\b[^\s]+\b)/', $sentence, $matches) ? $matches[0] : [];
}

function text_filter_non_utf8($text)
{
	$regex = <<<'END'
	/
	  (
	    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
	    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
	    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
	    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
	    ){1,100}                        # ...one or more times
	  )
	| .                                 # anything else
	/x
END;

	return preg_replace($regex, '$1', $text);
}

function text_fix_utf8($text)
{
	static $regex = <<<'END'
	/
	  (
		(?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
		|   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
		|   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
		|   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
		){1,100}                      # ...one or more times
	  )
	| ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
	| ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
	/x
END;

	$replace = function ($captures) {
		if ($captures[1] != "") {
			// Valid byte sequence. Return unmodified.
			return $captures[1];
		}
		elseif ($captures[2] != "") {
			// Invalid byte of the form 10xxxxxx.
			// Encode as 11000010 10xxxxxx.
			return "\xC2".$captures[2];
		}
		else {
			// Invalid byte of the form 11xxxxxx.
			// Encode as 11000011 10xxxxxx.
			return "\xC3".chr(ord($captures[3])-64);
		}
	};

	return preg_replace_callback($regex, $replace, $text);
}

function text_sentence_score($sentence, $query, $language)
{
	$occurrences = 0;
	$keyword_hits = 0;

	$stemmed_keywords = array_map([text_stemmer($language), 'stem'], parse_search_query('/\s+/', $query));

	$stemmed_sentence = array_map([text_stemmer($language), 'stem'], split_words($sentence));

	foreach ($stemmed_keywords as $stemmed_keyword) {
		$hits = count(array_keys($stemmed_sentence, $stemmed_keyword));
		$occurrences += $hits;
		$keyword_hits += $hits > 0 ? 1 : 0;
	}

	return $keyword_hits + ($occurrences / strlen($sentence));
}

function text_stemmer($language)
{
	static $stemmers = [];

	static $classes = [
		'nl' => 'Wamania\Snowball\Dutch',
		'en' => 'Wamania\Snowball\English'
	];

	return isset($stemmers[$language])
		? $stemmers[$language]
		: $stemmers[$language] = (new ReflectionClass($classes[$language]))->newInstance();
}

function text_summary($text, $query, $language = 'en')
{
	$sentences = [];

	foreach (split_sentences($text) as $sentence) {
		$sentences[] = [
			'sentence' => $sentence,
			'score' => text_sentence_score($sentence, $query, $language)
		];
	}

	usort($sentences, function($a, $b) {
		if ($b['score'] == $a['score'])
			return 0;
		else
			return $b['score'] > $a['score'] ? 1 : -1;
	});

	return count($sentences) > 0 ? $sentences[0]['sentence'] : null;
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
