<?php

$html = file_get_contents('https://www.svcover.nl/');

$dom = new DOMDocument;
@$dom->loadHTML($html);

function encode_ceacar($text)
{
	$table = array();

	foreach (range('a', 'z') as $letter)
		$table[$letter] = chr(((ord($letter) - ord('a') + 3) % 26) + ord('a'));

	foreach (range('A', 'Z') as $letter)
		$table[$letter] = chr(((ord($letter) - ord('A') + 3) % 26) + ord('A'));

	for ($i = 0; $i < strlen($text); ++$i)
	{
		$text{$i} = isset($table[$text{$i}])
			? $table[$text{$i}]
			: $text{$i};
	}

	return $text;
}

function encode_morse($text)
{
	$lettertomorse = array(
		"a" => ".-",
		"b" => "-...",
		"c" => "-.-.",
		"d" => "-..",
		"e" => ".",
		"f" => "..-.",
		"g" => "--.",
		"h" => "....",
		"i" => "..",
		"j" => ".---",
		"k" => ".-.",
		"l" => ".-..",
		"m" => "--",
		"n" => "-.",
		"o" => "---",
		"p" => ".--.",
		"q" => "--.-",
		"r" => ".-.",
		"s" => "...",
		"t" => "-",
		"u" => "..-",
		"v" => "...-",
		"w" => ".--",
		"x" => "-..-",
		"y" => "-.--",
		"z" => "--..",
		"1" => ".----",
		"2" => "..---",
		"3" => "...--",
		"4" => "....-",
		"5" => ".....",
		"6" => "-....",
		"7" => "--...",
		"8" => "---..",
		"9" => "----.",
		"0" => "-----",
		" " => "   ",
		"." => ".-.-.-",
		"," => "--..--",
		"\n" => "\n");

	$text = strtolower($text);

	$out = '';

	for ($i = 0; $i < strlen($text); ++$i)
		if (isset($lettertomorse[$text{$i}]))
			$out .= $lettertomorse[$text{$i}] . ' ';

	return str_replace('.', '•', $out);
}

function transform_dom_node(DOMNode $node)
{
	$ignored_elements = array('script', 'style');

	if ($node->hasChildNodes())
		foreach ($node->childNodes as $child)
			transform_dom_node($child);
	
	if ($node instanceof DOMCharacterData)
	{
		if ($node->parentNode && in_array($node->parentNode->nodeName, $ignored_elements))
			return;

		$node->replaceData(0, $node->length, encode_morse($node->data));
	}
}

transform_dom_node($dom);

echo $dom->saveHTML();
