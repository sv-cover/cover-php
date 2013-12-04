<?php

$site = file_get_contents('http://maikelzitopdingen.nl/');

if (!preg_match_all('~<h2>(.+?)</h2><h6>(.+?)</h6><img src="(.+?)"~', $site, $matches, PREG_PATTERN_ORDER))
	return;

if (count($matches) == 0)
	return;

$random_maikel = mt_rand(0, count($matches[1]) - 1);

$caption = $matches[1][$random_maikel];
$src = $matches[3][$random_maikel];

function encode_url($url)
{
	$parts = explode('/', $url);

	$parts = array_map('rawurlencode', $parts);

	return implode('/', $parts);
}
?>
<div class="maikel">
	<h1><?=htmlspecialchars($caption, ENT_COMPAT, 'utf-8')?></h1>
	<div style="
		position: absolute;
		top: -50px;
		left: -50px;
		bottom: -50px;
		right: -50px;
		background-image: url(http://maikelzitopdingen.nl/<?=encode_url($src)?>);
		background-position: center;
		background-repeat: no-repeat;
		background-size: cover;
		-webkit-filter: blur(40px);"></div>
	<div style="
		position: absolute;
		top: 100px;
		left: 50px;
		bottom: 50px;
		right: 50px;
		background-image: url(http://maikelzitopdingen.nl/<?=encode_url($src)?>);
		background-position: center;
		background-repeat: no-repeat;
		background-size: contain;"></div>
</div>