<?php

require_once 'include/data/DataModel.php';

class DataIterSession extends DataIter
{
	static public function fields()
	{
		return [
			'session_id',
			'member_id',
			'ip_address',
			'application',
			'created_on',
			'last_active_on',
			'timeout',
		];
	}
}

/**
  * A class implementing news data
  */
class DataModelSession extends DataModel
{
	public $dataiter = 'DataIterSession';

	public function __construct($db)
	{
		parent::__construct($db, 'sessions', 'session_id');
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
			$update = array(
				'last_active_on' => 'NOW()',
				'ip_address' => $_SERVER['REMOTE_ADDR']);

			$this->db->update($this->table, $update, $id_string, array('last_active_on'));

			return $this->_row_to_iter($data);
		}
		else
			return $data;
	}

	public function create($member_id, $application, $timeout = '7 DAY')
	{
		$session_id = bin2hex(openssl_random_pseudo_bytes(20));

		$data = array(
			'session_id' => $session_id,
			'member_id' => (int) $member_id,
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'application' => $application,
			'created_on' => date('Y-m-d H:i:s'),
			'last_active_on' => date('Y-m-d H:i:s'),
			'timeout' => $timeout
		);

		$iter = new DataIterSession($this, $session_id, $data);

		$this->insert($iter);

		return $iter;
	}

	public function getActive($member_id)
	{
		$query = sprintf("SELECT * FROM {$this->table} WHERE member_id = %d AND last_active_on + timeout > NOW()", $member_id);

		$result = $this->db->query($query);

		return $this->_rows_to_iters($result);
	}

	/**
	 * Returns a calendar session with a bit longer time to live. When exporting
	 * links with a session id for external services (like Google Calendar) you
	 * should use this function. This way when the user logs off he/she does not
	 * destroy the session used by that external service.
	 *
	 * @param int $member_id
	 * @param string $application the application identifier/name
	 * @return DataIterSession the session
	 */
	public function getForApplication($member_id, $application)
	{
		$sessions = $this->getActive($member_id);

		foreach ($sessions as $session)
			if ($session->get('application') == $application)
				return $session;

		// None found, let's create one
		return $this->create($member_id, $application, '1 MONTH');
	}
}
