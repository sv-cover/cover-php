<?php

namespace Cover\svg;

function barchart($data, $class='barchart')
{
	// Inspiration: https://bost.ocks.org/mike/bar/3/

	if (count($data) === 0)
		return null;
	
	$chart_width = 350;

	$chart_height = 200;

	$padding = 2;

	$bar_width = (($chart_width - 40 - 2 * $padding) / count($data)) - 2 * $padding;

	$data_max = max($data);

	$data_scale = 1.0 / $data_max;

	$bar_scale = ($chart_height - 40) / $data_max;

	$tick_scale = max(1, min($data_max, 10)); // At most 10 ticks

	$x_ticks = [];

	$y_ticks = [];

	$bars = [];

	$i = 0;

	foreach ($data as $partition => $count)
	{
		$bar_height = $count * $bar_scale;

		$x_ticks[] = sprintf('
			<g class="tick" transform="translate(%d, 0)">
				<line stroke="#000" y2="6" x1="0.5" x2="0.5"></line>
				<text fill="#000" y="9" x="0.5" dy="0.71em">%s</text>
			</g>',
			$bar_width / 2 + $padding + ($bar_width + 2 * $padding) * $i,
			$partition);

		$bars[] = sprintf('<rect class="barchart-bar" x="%d" y="%d" width="%d" height="%d" fill="#36749d"><title>%d</title></rect>',
			$padding + ($bar_width + 2 * $padding) * $i,
			$chart_height - $bar_height,
			$bar_width,
			$bar_height,
			$count);
		
		$i += 1;
	}

	for ($i = 0; $i <= $tick_scale; ++$i)
	{
		$value = (1.0 - $i / $tick_scale) / $data_scale;
		$bar_height = $value * $bar_scale;

		$y_ticks[] = sprintf('
			<g class="tick" transform="translate(0, %d)">
				<line stroke="#000" x2="-6" y1="0.5" y2="0.5"></line>
				<text fill="#000" x="-9" y="0.5" dy="0.32em">%d</text>
			</g>',
			$chart_height - $bar_height - $padding,
			round($value));
	}

	return sprintf('
		<svg width="%d" height="%d" class="%s" viewbox="0 0 %1$d %2$d">
			<g transform="translate(40, -20)">
				<g class="axis axis--x" transform="translate(0, %2$d)" fill="none" font-size="10" font-family="sans-serif" text-anchor="middle">
					%s
				</g>
				<g class="axis axis--y" fill="none" font-size="10" font-family="sans-serif" text-anchor="end">
					<!--<path class="domain" stroke="#000" d="M-6,%2$d.5H0.5H-6"></path>-->
					%s
				</g>
				%s
			</g>
		</svg>',
			$chart_width,
			$chart_height,
			markup_format_attribute($class),
			implode("\n", $x_ticks),
			implode("\n", $y_ticks),
			implode("\n", $bars));
}

