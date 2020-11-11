<?php

require_once 'include/data/DataModel.php';

class DataIterMailinglistQueue extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'destination',
			'destination_type',
			'mailinglist_id',
			'message',
			'status',
			'queued_on',
			'processing_on'
		];
	}

	public function get_mailinglist()
	{
		return get_model('DataModelMailinglist')->get_iter($this['mailinglist_id']);
	}

	public function header($name)
	{
		$end_header = strpos($this->get('message'), "\n\n");

		// If that didn't work, try \r\n, which may occur if the system configuration changed
		if ($end_header === false)
			$end_header = strpos($this->get('message'), "\r\n\r\n");

		// Still false? Give up.
		if ($end_header === false)
			return null;

		return preg_match('/^' . preg_quote($name) . ': (.+?)$/im', substr($this->get('message'), 0, $end_header), $match)
			? $this->_convert_header_encoding($match[1])
			: null;
	}

	public function get_subject()
	{
		return $this->header('Subject');
	}

	public function get_sender()
	{
		// This works because the mail server adds a 'from real@email.com wed 20 aug' to the
		// beginning of the message. Alternatively, we could use the From header.
		// return substr($this->get('bericht'), 5, strpos($this->get('bericht'), ' ', 5) - 5);
		return $this->header('From');
	}
}

class DataModelMailinglistQueue extends DataModel
{
	public $dataiter = 'DataIterMailinglistQueue';

	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten_queue');
	}

	public function queue($message, $destination, $destination_type, $list=null, $status='waiting')
	{
		$data = array(
			'destination' => $destination,
			'destination_type' => $destination_type,
			'mailinglist_id' => $list ? $list->get('id') : null,
			'message' => $message,
			'status' => $status,
		);

		$iter = $this->new_iter($data);

		$this->insert($iter);
	}
}

	
