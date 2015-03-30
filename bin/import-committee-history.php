#!/usr/bin/env php
<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';
require_once 'include/member.php';

putenv('LANG=UTF-8');
setlocale(LC_ALL, 'UTF-8');

function format_coverage($string, array $coverage)
{
	$out = '';

	for ($i = 0; $i <= strlen($string); ++$i) {
		foreach ($coverage as $match) {
			if ($match[0] == $i)
				$out .= "\033[44m\033[37m";

			if ($match[0] + $match[1] == $i)
				$out .= "\033[0m";
		}

		if ($i < strlen($string))
			$out .= $string[$i];
	}

	return $out;
}

function make_pattern($string)
{
	return str_replace(['é', 'ë', 'è', 'ö'], '.', preg_quote(trim($string), '/'));
}

$membership_model = get_model('DataModelActieveLeden');

$commissienamen = array(
	'ABCee' => 11,
	'StudCie' => 11,

	'ActiviTee' => 2,
	'Actie' => 2,
	
	'AlmanacCee' => 4,
	'AlmanakCie' => 4,
	'AlamanakCie' => 4,

	'EerstejaarsCie' => 17,
	
	'AphrodiTee' => 25,
	'MeisCie' => 25,
	
	'AudiCee' => 12,
	'KasCie' => 12,

	'Board of Advisors' => 14,
	'Raad van Advies' => 14,
	'RVA' => 14,
	
	'BoekCie' => 3,
	'BookCie' => 3,
	'BookCee' => 3,

	'Brainstorm' => 5,
	
	'ComExA' => 26,
	'PRCie' => 26,
	'PRCee' => 26,

	'ExCee' => 6,
	'ExCie' => 6,

	'Foetsie' => 15,

	'HEROcee' => 27,
	'BHVcie' => 27,
	'HEROcie' => 27,

	'IntroCee' => 8,
	'IntroCie' => 8,

	'LANcee' => 21,
	'LanCie' => 21,

	'LustrumCee' => 9,
	'LustrumCie' => 9,

	'Memory' => 18,
	'SLACKcie' => 18,

	'McCee' => 13,
	'McCie' => 13,
	'MxCee' => 13,
	
	'PCie' => 22,
	'PCee' => 22,
	'PC' => 22,
	
	'PhotoCee' => 7,
	'FotoCie' => 7,

	'PubliciTee' => 24,
	'Promotie' => 24,
	'Promitie' => 24,

	'SporTee' => 20,
	'Conditie' => 20,
	
	'SympoCee' => 16,
	'Sympocie' => 16,

	'StudCee' => 31,
	'Study Support Committee' => 31,

	'WebCie' => 1, 

	'Bestuur' => 0
);

$commissies = array();

foreach ($commissienamen as $commissie => $id)
	$commissies[strtolower($commissie)] = get_model('DataModelCommissie')->get_iter($id);

$member_iter = get_model('DataModelMember')->get();

$members = array();
foreach ($member_iter as $iter)
	if (trim($iter->get('voornaam')) != '') {
		$name_pattern = sprintf('\b%s%s %s',
			make_pattern($iter->get('voornaam')),
			trim($iter->get('tussenvoegsel')) ? '( ' . make_pattern($iter->get('tussenvoegsel')) . ')?' : '',
			make_pattern($iter->get('achternaam'))
		);
		
		$members[(int) $iter->get_id()] = array('pattern' => $name_pattern, 'iter' => $iter);
	}

function import_from_file($fh)
{
	global $commissies, $members;

	$header_1 = fgetcsv($fh, 0, "\t");
	$header_2 = fgetcsv($fh, 0, "\t");

	$n_row = 2;
	while ($row = fgetcsv($fh, 0, "\t"))
	{
		$n_row++;
		$commissielid = null;

		foreach ($members as $member) {
			if (preg_match('/^' . $member['pattern'] . '$/i', trim($row[0]))) {
				$commissielid = $member['iter'];
				break;
			}
		}

		if (!$commissielid) {
			echo "SKIP: $n_row:0 Kan geen commissielid vinden in {$row[0]}\n";
			continue;
		}

		for ($i = 1; $i < count($row); ++$i)
		{
			if ($row[$i] == 0)
				continue;

			if (!preg_match('/^(Begin|Eind) (' . implode('|', array_keys($commissies)) . ')$/i', $header_2[$i], $match)) {
				// echo "SKIP: $n_row:$i geen begin of eind + commissie in {$header_2[$i]}\n";
				continue;
			}
			elseif (strtolower($match[1]) == 'begin')
				$actie = 'ingehamerd';
			elseif (strtolower($match[1]) == 'eind')
				$actie = 'uitgehamerd';

			$commissie = $commissies[strtolower($match[2])];

			if (!preg_match('/^(\d\d)[^\d](\d\d)[^\d](\d\d)$/', $row[$i], $match)) {
				echo "SKIP: $n_row:$i kan datum niet parsen\n";
				continue;
			}
			else {
				$date = new DateTime(sprintf('20%02d-%02d-%02d', $match[3], $match[2], $match[1]));
			}

			commit_membership($actie, $commissie, [$commissielid], $date);
		}
	}
}

function import_from_sd()
{
	global $commissies, $members;

	echo "Connecting to MySQL\n";
	$pdo = new PDO('mysql:host=127.0.0.1;port=33060;dbname=sd', 'sd', $argv[1]);

	echo "Getting documents\n";
	$rows = $pdo->query('SELECT uid, path, keywords FROM files WHERE path LIKE "/home/bestuur/bestuur/standaarddocumenten/Minutes/%" AND path LIKE "%.pdf"');

	echo "Magic!\n";
	foreach ($rows as $row)
	{
		$date = null;

		if (preg_match('/\/[^\/]*(20\d{2})-?(\d{1,2})-?(\d{1,2})[^\/]+$/', $row['path'], $match))
			$date = new DateTime(sprintf('%04d-%02d-%02d', $match[1], $match[2], $match[3]));
		
		elseif (preg_match('/\/[^\/]*(\d{1,2})-(\d{1,2})-(20\d{2})[^\/]+$/', $row['path'], $match))
			$date = new DateTime(sprintf('%04d-%02d-%02d', $match[3], $match[2], $match[1]));

		else
			continue;
		
		if (!preg_match_all('/^(Besluit|Hamerslag)(?!en)(.+?)(\s{2,})/ism', $row['keywords'], $matches))
			continue;

		foreach ($matches[2] as $i => $line)
		{
			$line = preg_replace("/\d+/", " ", $line);

			$line = preg_replace("/\s+/", " ", $line);

			$line = trim($line);

			$commissie = null;

			$actie = null;

			$commissieleden = array();

			$coverage = array();
			
			if (preg_match('/(uit|in)ge(-\s)?hamerd/i', $line, $match, PREG_OFFSET_CAPTURE)
				|| preg_match('/(uit|in)\sde\s(.+?)\sge(-\s)?hamerd/', $line, $match, PREG_OFFSET_CAPTURE)
				|| preg_match('/gaat\sde\s(.+?)\s(uit|in)/', $line, $match, PREG_OFFSET_CAPTURE)) {

				$actie = $match[1][0] . 'gehamerd';
				$coverage[] = array($match[0][1], strlen($match[0][0]));
			}
			
			if (preg_match('/' . implode('|', array_keys($commissies)) . '/i', $line, $match, PREG_OFFSET_CAPTURE)) {
				$commissie = $commissies[strtolower($match[0][0])];
				$coverage[] = array($match[0][1], strlen($match[0][0]));
			}
			
			foreach ($members as $id => $member) {
				if ((int) $member['iter']->get('beginjaar') > (int) $date->format('Y'))
					continue;

				if (preg_match('/' . $member['pattern'] . '/is', $line, $match, PREG_OFFSET_CAPTURE)) {
					$commissieleden[$id] = $member['iter'];
					$coverage[] = array($match[0][1], strlen($match[0][0]));
				}
			}

			if (!empty($commissieleden) && $actie && $commissie)
				commit_membership($actie, $commissie, $commissieleden, $date);
			
			echo "\n" . format_coverage($line, $coverage) . "\n";
			printf("%s %s %s %s: %s\033[0m\n",
				!empty($commissieleden) && $actie && $commissie
					? "\033[42m✓"
					: "\033[41m✗",
				$date->format('Y-m-d'),
				$actie ? $actie : '[NULL]',
				$commissie ? $commissie->get('naam') : '[NULL]',
				implode(', ', array_map('member_full_name', $commissieleden)));
		}
	}
}

function commit_membership($actie, $commissie, $commissieleden, $date)
{
	global $membership_model;

	if ($actie == 'ingehamerd') {
		foreach ($commissieleden as $commissielid) {
			$membership_model->start_membership($commissie, $commissielid, null, $date);
		}
	}
	elseif ($actie == 'uitgehamerd') {
		foreach ($commissieleden as $commissielid) {
			$membership = $membership_model->find_membership($commissie, $commissielid, $date);

			if (!$membership)
				$membership = $membership_model->start_membership($commissie, $commissielid, null, null);
			
			$membership_model->end_membership($membership->get_id(), $date);
		}
	}
}

if ($argc > 1)
	import_from_sd();
else
	import_from_file(fopen('php://stdin', 'r'));
