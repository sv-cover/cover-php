<?php

require_once 'src/framework/cache.php';

class CoverFacebook
{
	protected $storage;
	
	private $session;

	public function __construct(CoverFacebookStorage $storage, Cache $cache)
	{
		$this->storage = $storage;

		$this->cache = $cache;

		$this->cache_ttl = 600;
	}

	public function getUser()
	{
		throw new Exception('Facebook sessions not yet implemented using the new Facebook 4 API');
	}

	protected function api($url, $method)
	{
		if (!isset($this->session))
			$this->session = \Facebook\FacebookSession::newAppSession();

		$request = new \Facebook\FacebookRequest($this->session, $method, $url);

		return $request->execute();
	}

	public function getAttending($event_id)
	{
		if (($attendees = $this->cache->get('attendees_' . $event_id)) !== null)
			return $attendees;
		
		try {
			$response = $this->api('/' . $event_id . '/attending?fields=name,picture', 'GET');
			
			$attendees = array();

			$data = $response->getGraphObject();

			if ($data->getProperty('data'))
				foreach ($data->getProperty('data')->asArray() as $attendee)
					$attendees[] = array(
						'id' => $attendee->id,
						'name' => $attendee->name
					);
		}
		catch (\Facebook\FacebookSDKException $e) {
			$attendees = array();
		}

		$this->cache->put('attendees_' . $event_id, $attendees, $this->cache_ttl);

		return $attendees;
	}

	public function getCoverPhoto($event_id)
	{
		$undefined = new stdClass();

		if (($cover_photo = $this->cache->get('cover_photo_' . $event_id, $undefined)) !== $undefined)
			return $cover_photo;

		try {
			$response = $this->api('/' . $event_id . '?fields=cover', 'GET');

			$cover_photo = null;

			$facebook_event = $response->getGraphObject();

			if ($facebook_event->getProperty('cover'))
			{
				$facebook_image = $this->api('/' . $facebook_event->getProperty('cover')->getProperty('id') . '?fields=width,height', 'GET')->getGraphObject()->asArray();

				if (isset($facebook_image['height'], $facebook_image['width']))
				{
					$real_img_h = 784 * $facebook_image['height'] / $facebook_image['width'] - 295;
					
					$cover_photo = array(
						'src' => $facebook_event->getProperty('cover')->getProperty('source'),
						'x' => $facebook_event->getProperty('cover')->getProperty('offset_x') / 784 * 100,
						'y' => $real_img_h * $facebook_event->getProperty('cover')->getProperty('offset_y') / 295);

				}
			}
		}
		catch (\Facebook\FacebookSDKException $e) {
			$cover_photo = null;
		}

		$this->cache->put('cover_photo_' . $event_id, $cover_photo, $this->cache_ttl);

		return $cover_photo;
	}
}

class CoverFacebookStorage
{
	private $cache = array();

	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	protected function setPersistentData($key, $value)
	{
		if (!get_auth()->logged_in())
			throw new Exception('Cannot store persistent data while not logged in');

		$data = array(
			'lid_id' => get_identity()->get('id'),
			'data_key' => $key,
			'data_value' => $value);

		if (!$this->keyInDatabase($key))
			$this->db->insert('facebook', $data);
		else
			$this->db->update('facebook', $data, array(
				'lid_id' => get_identity()->get('id'),
				'data_key' => $key));

		$this->cache[$key] = $value;
	}

	protected function getPersistentData($key, $default = false)
	{
		if (!get_auth()->logged_in())
			return $default;

		if (!isset($this->cache[$key]))
		{
			$query = sprintf("SELECT data_value FROM facebook WHERE lid_id = %d AND data_key = '%s'",
				get_identity()->get('id'), $this->db->escape_string($key));

			$this->cache[$key] = $this->db->query_value($query);
		}

		return $this->cache[$key] !== null ? $this->cache[$key] : $default;
	}

	protected function clearPersistentData($key)
	{
		if (!get_auth()->logged_in())
			throw new Exception('Cannot store persistent data while not logged in');

		$query = sprintf("DELETE FROM facebook WHERE lid_id = %d AND data_key = '%s'",
				get_identity()->get('id'), $this->db->escape_string($key));

		$this->db->query($query);

		$this->cache[$key] = null;
	}

	protected function clearAllPersistentData()
	{
		if (!get_auth()->logged_in())
			throw new Exception('Cannot store persistent data while not logged in');

		$query = sprintf("DELETE FROM facebook WHERE lid_id = %d",
				get_identity()->get('id'));

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

	public function getCoverLoginURL()
	{
		return $this->getLoginURL(array('scope' => 'rsvp_event'));
	}
}

function get_facebook()
{
	static $facebook;

	if ($facebook === null) {
		\Facebook\FacebookSession::setDefaultApplication(
			get_config_value('facebook_app_id'),
			get_config_value('facebook_app_secret'));

		$facebook = new CoverFacebook(new CoverFacebookStorage(get_db()), get_cache());
	}

	return $facebook;
}

