<?php

function parse_options($argv, array &$options)
{
	$argc = count($argv);
	$argi = 1;

	for ($stop_parsing = false; $argi < $argc && !$stop_parsing; ++$argi)
	{
		if (!preg_match('/^--(?<name>[a-z]+)(?:\=(?<value>.+))?$/i', $argv[$argi], $option)) {
			$stop_parsing = true;
			break;
		}

		if (!isset($options[$option['name']]))
			die("Unknown option: --{$option['name']}\n");

		$options[$option['name']] = isset($option['value']) ? $option['value'] : true;
	}

	return array_splice($argv, $argi);
}