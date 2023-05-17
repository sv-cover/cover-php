<?php

abstract class WebCal
{
	protected function _encode($text)
	{
		$encoding = array(
			"\r" => '',
			"\n" => '\n',
			"\\" => '\\\\',
			 ";" => '\\;',
			 "," => '\\,'
		);

		return strtr($text, $encoding);
	}
}

class WebCal_Calendar extends WebCal
{
	public $events = array();

	public $name;
	
	public $description;

	public $additional_headers = array();

	public function __construct($name)
	{
		$this->name = $name;
	}
	
	public function add_event(WebCal_Event $event)
	{
		$this->events[] = $event;
	}
	
	public function export_headers()
	{
		$out = array(
			'METHOD:PUBLISH',
			'CALSCALE:GREGORIAN',
			'VERSION:2.0',
			'PRODID:-//svCover.nl//NONSGML Cover Calendar v1.0//EN',
			'X-WR-CALNAME:' . $this->_encode($this->name),
			'X-WR-CALDESC:' . $this->_encode($this->description),
			'X-WR-RELCALID:' . md5($this->name)
		);

		return array_merge($out, $this->additional_headers);
	}

	public function export()
	{
		$out = array_merge(
			['BEGIN:VCALENDAR'],
			$this->export_headers()
		);
		
		foreach ($this->events as $event)
			$out[] = $event->export();
		
		$out[] = 'END:VCALENDAR';
		
		return implode("\r\n", $out);
	}
	
	public function publish($filename = null)
	{
		header('Content-Type: text/calendar; charset=UTF-8');
		
		if ($filename)
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		
		echo $this->export();
	}

	public function inject($ical)
	{
		preg_match_all('/(*ANYCRLF)^BEGIN:VTIMEZONE$.*?^END:VTIMEZONE$/sm', $ical, $timezones);
		$this->additional_headers = array_merge(
			$this->additional_headers,
			$timezones[0]
		);

		preg_match_all('/(*ANYCRLF)^BEGIN:VEVENT$.*?^END:VEVENT$/sm', $ical, $events);
		foreach ($events[0] as $event)
			$this->add_event(new WebCal_External_Event($event));
	}
}

class WebCal_Event extends WebCal
{
	public $uid;

	public $start;
	
	public $end;
	
	public $summary;
	
	public $description;
	
	public $location;
	
	public $url;
	
	public function export()
	{
		$start = $this->start;
		if (!($this->start instanceof DateTime))
			// Use Cover birthday in case of parse errors…
			$start = new DateTime($this->start) ?? new DateTime('1993-09-20');
		
		$end = $this->end;
		if ($this->end && !($this->end instanceof DateTime))
			// End is optional, so no need for fallback
			$end = new DateTime($this->end);

		$headers = [];

		$out = array('BEGIN:VEVENT');

		// Is this an whole day event? If so, only add the date.
		if ($start->format('Hi') == '0000' && (!$end || $end->format('Hi') == '0000'))
		{
			// Is there an end date? Yes? Good! Otherwise, make the event one day long.
			if (!$end)
				$end = DateTime::createFromInterface($start)->modify('+1 day');
			
			$out[] = 'DTSTART;VALUE=DATE:' . $start->format('Ymd');
			$out[] = 'DTEND;VALUE=DATE:' . $end->format('Ymd');
		}
		// Maybe it's a multiday event?
		elseif ($end && $end->diff($start)->days >= 1)
		{
			$out[] = 'DTSTART;VALUE=DATE:' . $start->format('Ymd');
			$out[] = 'DTEND;VALUE=DATE:' . $end->format('Ymd');

			// Use array_merge to get these headers to the top
			$headers = array_merge([
				'Start: ' . $start->format('Y-m-d H:i'),
				'End: ' . $end->format('Y-m-d H:i'),
			], $headers);
		}
		// No it is not, just add date and time.
		else
		{
			$start->setTimezone(new DateTimeZone('GMT'));
			// Is there an end time? No? Let the event be till the end of the day.
			if (!$end)
				$end = DateTime::createFromInterface($start)->modify('00:00:00')->modify('+1 day');
			else
				$end->setTimezone(new DateTimeZone('GMT'));

			$out[] = 'DTSTART:' . $start->format('Ymd\THis\Z');
			$out[] = 'DTEND:' . $end->format('Ymd\THis\Z');
		}

		// Add some optional fields to the calendar item
		
		if ($this->uid)
			$out[] = 'UID:' . $this->_encode($this->uid);

		if ($this->summary)
			$out[] = 'SUMMARY:' . $this->_encode($this->summary);

		if ($this->location)
			$out[] = 'LOCATION:' . $this->_encode($this->location);

		if ($this->url)
			$headers[] = 'More information: ' . $this->url;

		if ($this->description && !empty($headers))
			$this->description = sprintf("%s\n\n%s", implode("\n", $headers), $this->description);
		elseif(!empty($headers))
			$this->description = implode("\n", $headers);
		$out[] = 'DESCRIPTION:' . $this->_encode($this->description ?? '');


		// URL is used for ics url
		// if ($this->url)
		// 	$out[] = 'URL;VALUE=URI:' . $this->_encode($this->url);

		$out[] = 'END:VEVENT';
		
		return implode("\r\n", $out);
	}
}


class WebCal_External_Event extends WebCal_Event
{
	public $content;

	public function __construct($content)
	{
		$this->content = $content;
	}

	public function export()
	{
		return $this->content;
	}
}
