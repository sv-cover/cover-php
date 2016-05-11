<?php
require_once 'include/markup.php';

class SessionsView extends View
{
	protected $__file = __FILE__;

	protected function user_can_override_stuff()
	{
		return get_identity() instanceof ImpersonatingIdentityProvider;
	}

	protected function format_relative_time($time)
	{
		return format_date_relative($time);
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
			'Microsoft Edge' => 'Edge',
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

	protected function format_application($application)
	{
		return sprintf('<abbr title="%s">%s</a>',
			markup_format_text($application),
			markup_format_text($this->format_nice_application($application)));
	}
}
