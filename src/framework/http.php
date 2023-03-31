<?php

class HTTPEventStream
{
	public function start()
	{
		ob_end_clean();
		ob_implicit_flush(true);

		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');	
	}

	public function event($event, $data = '')
	{
		echo "event: $event\ndata: $data\n\n";
		
		while (ob_get_level())
			ob_flush();
	}
}