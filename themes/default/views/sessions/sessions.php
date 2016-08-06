<?php
require_once 'include/markup.php';

class SessionsView extends View
{
	public function render_overrides()
	{
		$committees = get_model('DataModelCommissie')->get();

		return $this->twig->render('overrides.twig', compact('committees'));
	}

	public function render_sessions($sessions, $member, $session = null)
	{
		return $this->twig->render('sessions.twig', compact('sessions', 'member', 'session'));
	}

	public function render_login($errors, $error_message = null, $referrer = null, $external_domain = null)
	{
		return $this->twig->render('login.twig', compact('errors', 'error_message', 'referrer', 'external_domain'));
	}

	public function render_logout()
	{
		return $this->twig->render('logout.twig');
	}

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

	public function format_application($application)
	{
		return sprintf('<abbr title="%s">%s</a>',
			markup_format_text($application),
			markup_format_text($this->format_nice_application($application)));
	}
}
