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

	public function resume($id)
	{
		$id_string = $this->_id_string($id);

		$data = $this->db->query_first("
			SELECT *
			FROM {$this->table}
			WHERE
				{$id_string}
				AND (last_active_on + timeout) > NOW()
			");

		if ($data)
		{
			// Update last active
			$this->db->query("UPDATE {$this->table} SET last_active_on = NOW() WHERE {$id_string}");

			return new $this->dataiter($this, $data[$this->id], $data);
		}
		else
			return $data;
	}

	public function create($member_id, $ip_address, $application, $timeout = '7 DAY')
	{
		$session_id = sha1(uniqid('session', true));

		$data = array(
			'session_id' => $session_id,
			'member_id' => (int) $member_id,
			'ip_address' => $ip_address,
			'application' => $application,
			'created_on' => date('Y-m-d H:i:s'),
			'last_active_on' => date('Y-m-d H:i:s'),
			'timeout' => $timeout
		);

		$iter = new DataIter($this, -1, $data);

		$this->insert($iter);

		return $iter;
	}

	public function getActive($member_id)
	{
		$query = sprintf("SELECT * FROM {$this->table} WHERE member_id = %d AND last_active_on + timeout > NOW()", $member_id);

		$result = $this->db->query($query);

		return $this->_rows_to_iters($result);
	}

	public function destroy($session_id)
	{
		$this->db->delete($this->table,
			sprintf("session_id = '%s'", $this->db->escape_string($session_id)));

		return $this->db->get_affected_rows() == 1;
	}
}
