<?php

require_once 'src/data/DataModel.php';

class DataIterMailinglistArchive extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'mailinglijst',
			'commissie',
			'bericht',
			'sender',
			'return_code',
			'verwerkt_op'
		];
	}

	public function header($name)
	{
		$end_header = strpos($this->get('bericht'), "\n\n");

		// If that didn't work, try \r\n, which may occur if the system configuration changed
		if ($end_header === false)
			$end_header = strpos($this->get('bericht'), "\r\n\r\n");

		// Still false? Give up.
		if ($end_header === false)
			return null;

		return preg_match('/^' . preg_quote($name) . ': (.+?)$/im', substr($this->get('bericht'), 0, $end_header), $match)
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

	protected function _convert_header_encoding($data)
	{
		$decode = function($match) {
			switch ($match[2])
			{
				case 'Q':
					$data = quoted_printable_decode($match[3]);
					break;

				case 'B':
					$data = base64_decode($match[3]);
					break;
			}

			if (strcasecmp($match[1], 'utf-8') !== 0)
				$data = iconv($match[1], 'UTF-8//TRANSLIT', $data);

			return $data;
		};

		return preg_replace_callback('/=\?([a-zA-Z0-9_-]+)\?(Q|B)\?(.+?)\?=/', $decode, $data);
	}
}

class DataModelMailinglistArchive extends DataModel
{
	public $dataiter = 'DataIterMailinglistArchive';

	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten_berichten');
	}

	public function archive($bericht, $sender, $lijst, $commissie, $return_code)
	{
		$data = array(
			'bericht' => $bericht,
			'sender' => $sender,
			'mailinglijst' => $lijst ? $lijst->get('id') : null,
			'commissie' => $commissie ? $commissie->get('id') : null,
			'return_code' => $return_code
		);

		$iter = $this->new_iter($data);

		$this->insert($iter);
	}

	public function get_for_list(DataIterMailinglist $list)
	{
		return $this->find(['mailinglijst' => (int) $list['id']]);
	}

	public function count_for_list(DataIterMailinglist $list, $span_in_days = null)
	{
		$query = sprintf("SELECT COUNT(id) FROM {$this->table} WHERE mailinglijst = %d AND return_code = 0", $list->get_id());
		
		if ($span_in_days !== null)
			$query .= sprintf(" AND verwerkt_op > CURRENT_DATE - INTERVAL '%d days'", $span_in_days);

		return (int) $this->db->query_value($query);
	}

	public function contains_email_from(DataIterMailinglist $lijst, $sender)
	{
		$count = $this->db->query_value(sprintf("SELECT COUNT(id) FROM {$this->table} WHERE mailinglijst = %d AND sender = '%s' AND return_code = 0",
			$lijst->get_id(), $this->db->escape_string($sender)));

		return $count > 0;
	}

	protected function _generate_query($where)
	{
		return parent::_generate_query($where) . ' ORDER BY verwerkt_op DESC';
	}
}

	
