<?php

require_once('data/DataModel.php');

/**
  * A class implementing news data
  */
class DataModelSession extends DataModel
{
	public function DataModelSession($db)
	{
		parent::DataModel($db, 'sessions', 'session_id');
	}

	public function create($member_id)
	{
		$session_id = sha1(uniqid('session', true));

		$data = array(
			'session_id' => $session_id,
			'member_id' => (int) $member_id,
			'created_on' => date('Y-m-d H:i:s')
		);

		$iter = new DataIter($this, -1, $data);

		$this->insert($iter);

		return $iter;
	}

	public function destroy($session_id)
	{
		$data = compact('session_id');

		$iter = new DataIter($this, -1, $data);

		return $this->delete($iter);
	}
}
