<?php
require_once 'src/framework/markup.php';

class SessionsView extends View
{
	public function format_relative_time($time)
	{
		return format_date_relative($time);
	}

	public function format_time($timestring)
	{
		$time = strtotime($timestring);

		return sprintf('<span title="%s">%s</span>',
			date('d-m-Y H:i:s', $time),
			$this->format_relative_time($time));
	}

	public function format_nice_application($application)
	{
		$known_browsers = array(
			'Firefox' => 'Firefox',
			'Microsoft Edge (Legacy)' => 'Edge',
			'Microsoft Edge' => 'Edg',
			'Internet Explorer' => 'MSIE',
			'IE Mobile' => 'IEMobile',
			'iPad' => 'iPad',
			'Android' => 'Android',
			'Google Chrome' => 'Chrome',
			'Safari' => 'Safari',
			'iCal agenda feed' => 'calendar');

		foreach ($known_browsers as $name => $hint)
			if (stripos($application, $hint) !== false)
				return $name;

		return ucwords($application);
	}

	public function format_application($application)
	{
		return sprintf('<abbr title="%s">%s</a>',
			markup_format_text($application),
			markup_format_text($this->format_nice_application($application)));
	}
}
