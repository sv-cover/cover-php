<?php
require_once 'markup.php';

class SessionsView extends View
{
	protected $__file = __FILE__;

	protected function format_relative_time($time)
	{
		$diff = time() - $time;

		if ($diff == 0)
			return _('nu');

		else if ($diff > 0)
		{
			$day_diff = floor($diff / 86400);
			
			if ($day_diff == 0)
			{
				if ($diff < 60) return _('net');
				if ($diff < 120) return _('1 minuut geleden');
				if ($diff < 3600) return sprintf(_('%d minuten geleden'), floor($diff / 60));
				if ($diff < 7200) return _('1 uur geleden');
				if ($diff < 86400) return sprintf(_('%d uren geleden'), floor($diff / 3600));
			}
			if ($day_diff == 1) return _('Gisteren');
			if ($day_diff < 7) return sprintf(_('%d dagen geleden'), $day_diff);
			// if ($day_diff < 31) return sprintf(_('%d weken geleden'), floor($day_diff / 7));
			// if ($day_diff < 60) return _('afgelopen maand');
			return date('d-m-Y H:i:s', $time);
		}
		else
			return date('d-m-Y', $time);
	}

	protected function format_time($timestring)
	{
		$time = strtotime($timestring);

		return sprintf('<span title="%s">%s</span>',
			date('d-m-Y H:i:s', $time),
			$this->format_relative_time($time));
	}

	protected function format_nice_application($application)
	{
		$known_browsers = array(
			'Firefox' => 'Firefox',
			'MS Internet Explorer' => 'MSIE',
			'iPad' => 'iPad',
			'Android' => 'Android',
			'Chrome' => 'Google Chrome',
			'Safari' => 'Safari');

		foreach ($known_browsers as $name => $hint)
			if (stripos($application, $hint) !== false)
				return $name;

		return $application;
	}

	protected function format_application($application)
	{
		return sprintf('<abbr title="%s">%s</a>',
			markup_format_text($application),
			$this->format_nice_application($application));
	}
}
