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

	public function __construct($name)
	{
		$this->name = $name;
	}
	
	public function add_event(WebCal_Event $event)
	{
		$this->events[] = $event;
	}
	
	public function export()
	{
	 	$out = array(
			'BEGIN:VCALENDAR',
			'METHOD:PUBLISH',
			'CALSCALE:GREGORIAN',
			'VERSION:2.0',
			'PRODID:-//IkHoefGeen.nl//NONSGML v1.0//EN',
			'X-WR-CALNAME:' . $this->_encode($this->name),
			'X-WR-CALDESC:' . $this->_encode($this->description),
			'X-WR-RELCALID:' . md5($this->name)
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
}

class WebCal_Event extends WebCal
{
	public $start;
	
	public $end;
	
	public $summary;
	
	public $description;
	
	public $location;
	
	public $url;
	
	public function export()
	{
		
		$start = $this->start instanceof DateTime
			? $this->start->format('U')
			: $this->start;
		
		$end = $this->end instanceof DateTime
			? $this->end->format('U')
			: $this->end;

		$out = array(
			'BEGIN:VEVENT',
			'DTSTART:' . gmdate('Ymd\THis\Z', $start)
		);

		if ($end)
			$out[] = 'DTEND:' . gmdate('Ymd\THis\Z', $end);

		if ($this->summary)
			$out[] = 'SUMMARY:' . $this->_encode($this->summary);

		if ($this->description)
			$out[] = 'DESCRIPTION:' . $this->_encode($this->description);

		if ($this->location)
			$out[] = 'LOCATION:' . $this->_encode($this->location);

		if ($this->url)
			$out[] = 'URL;VALUE=URI:' . $this->_encode($this->url);

		$out[] = 'END:VEVENT';
		
		return implode("\r\n", $out);
	}
}
