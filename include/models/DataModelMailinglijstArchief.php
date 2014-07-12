<?php

require_once 'data/DataModel.php';

class DataIterMailinglijstArchief extends DataIter
{
	public function get_header($name)
	{
		$end_header = strpos($this->get('bericht'), "\n\n");

		return preg_match('/^' . preg_quote($name) . ': (.+?)$/im', substr($this->get('bericht'), 0, $end_header), $match)
			? $match[1]
			: null;
	}

	public function get_subject()
	{
		return $this->get_header('Subject');
	}

	public function get_sender()
	{
		// This works because the mail server adds a 'from real@email.com wed 20 aug' to the
		// beginning of the message. Alternatively, we could use the From header.
		// return substr($this->get('bericht'), 5, strpos($this->get('bericht'), ' ', 5) - 5);
		return $this->get_header('From');
	}
}

class DataModelMailinglijstArchief extends DataModel
{
	/*protected*/ var $dataiter = 'DataIterMailinglijstArchief';

	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten_berichten');
	}

	public function archive($bericht, $lijst, $commissie, $return_code)
	{
		$data = array(
			'bericht' => $bericht,
			'mailinglijst' => $lijst ? $lijst->get('id') : null,
			'commissie' => $commissie ? $commissie->get('id') : null,
			'return_code' => $return_code
		);

		$iter = new DataIter($this, -1, $data);

		$this->insert($iter);
	}

	public function get_by_lijst($lijst_id)
	{
		return $this->find('mailinglijst = ' . intval($lijst_id));
	}

	protected function _generate_query($where)
	{
		return parent::_generate_query($where) . ' ORDER BY verwerkt_op DESC';
	}
}

	
