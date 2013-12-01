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

	public function get_iter($id)
	{
		if (!$this->db || !$this->table)
			return null;

		$data = $this->db->query_first('SELECT * FROM ' . $this->table . 
				' WHERE ' . $this->_id_string($id) . ' AND (destroyed_on IS NULL OR destroyed_on > NOW())');

		if ($data)
			return new $this->dataiter($this, $data[$this->id], $data);
		else
			return $data;
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
		$this->db->update($this->table,
			array('destroyed_on' => date('Y-m-d H:i:s')),
			sprintf("session_id = '%s'", $this->db->escape_string($session_id)));

		return $this->db->get_affected_rows() == 1;
	}
}
