<?php

class CoverFacebook extends BaseFacebook
{
	private $cache = array();

	private $db;

	public function __construct($db, $options)
	{
		$this->db = $db;

		parent::__construct($options);
	}

	protected function setPersistentData($key, $value)
	{
		if (!logged_in())
			throw new Exception('Cannot store persistent data while not logged in');

		$data = array(
			'lid_id' => logged_in('id'),
			'data_key' => $key,
			'data_value' => $value);

		if (!$this->keyInDatabase($key))
			$this->db->insert('facebook', $data);
		else
			$this->db->update('facebook', $data, array(
				'lid_id' => logged_in('id'),
				'data_key' => $key));

		$this->cache[$key] = $value;
	}

	protected function getPersistentData($key, $default = false)
	{
		if (!logged_in())
			return $default;

		if (!isset($this->cache[$key]))
		{
			$query = sprintf("SELECT data_value FROM facebook WHERE lid_id = %d AND data_key = '%s'",
				logged_in('id'), $this->db->escape_string($key));

			$this->cache[$key] = $this->db->query_value($query);
		}

		return $this->cache[$key] !== null ? $this->cache[$key] : $default;
	}

	protected function clearPersistentData($key)
	{
		if (!logged_in())
			throw new Exception('Cannot store persistent data while not logged in');

		$query = sprintf("DELETE FROM facebook WHERE lid_id = %d AND data_key = '%s'",
				logged_in('id'), $this->db->escape_string($key));

		$this->db->query($query);

		$this->cache[$key] = null;
	}

	protected function clearAllPersistentData()
	{
		if (!logged_in())
			throw new Exception('Cannot store persistent data while not logged in');

		$query = sprintf("DELETE FROM facebook WHERE lid_id = %d",
				logged_in('id'));

		$this->db->query($query);

		foreach ($this->cache as $key => $value)
			$this->cache[$key] = null;
	}

	private function keyInDatabase($key)
	{
		if (isset($this->cache[$key]))
			return $this->cache[$key] !== null;

		return $this->cache[$key] = $this->getPersistentData($key, null);
	}
}

function get_facebook()
{
	static $facebook;

	if ($facebook === null)
		$facebook = new CoverFacebook(get_db(), array(
			'appId' => get_config_value('facebook_app_id'),
			'secret' => get_config_value('facebook_app_secret'),
			'fileUpload' => true
		));

	return $facebook;
}

