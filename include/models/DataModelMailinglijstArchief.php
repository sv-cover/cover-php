<?php

require_once 'data/DataModel.php';

class DataModelMailinglijstArchief extends DataModel
{
	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten_berichten');
	}

	public function archive($bericht, $lijst, $return_code)
	{
		$data = array(
			'bericht' => $bericht,
			'mailinglijst' => $lijst ? $lijst->get('id') : null,
			'return_code' => $return_code
		);

		$iter = new DataIter($this, -1, $data);

		$this->insert($iter);
	}
}

	