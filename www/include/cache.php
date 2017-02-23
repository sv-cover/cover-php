<?php

function get_cache()
{
	static $cache;

	return $cache ? $cache : $cache = new Cache(get_db());
}

function wrap_cache($object, $timeout = 600, $flags = 0)
{
	return new CacheDecorator(get_cache(), $object, $timeout, $flags);
}

class Cache
{
	protected $db;

	protected $table;

	public function __construct($db)
	{
		$this->db = $db;

		$this->table = 'cache';
	}

	public function put($key, $value, $ttl)
	{
		$this->delete($key);

		$data = array(
			'key' => $this->_hash($key),
			'value' => serialize($value),
			'expires' => time() + $ttl
		);

		$this->db->insert($this->table, $data);
	}

	public function get($key, $fallback = null)
	{
		$value = $this->db->query_value(sprintf("SELECT value FROM %s WHERE key = '%s' AND expires >= %d",
			$this->table,
			$this->_hash($key),
			time()));

		return $value !== null ? unserialize($value) : $fallback;
	}

	public function delete($key)
	{
		$this->db->delete($this->table, sprintf("key = '%s'", $this->_hash($key)));
	}

	protected function _hash($key)
	{
		return sha1($key);
	}
}

class CacheDecorator
{
	const CATCH_EXCEPTION = 1;

	public function __construct(Cache $cache, $delegate, $timeout, $flags = 0)
	{
		$this->cache = $cache;

		$this->delegate = $delegate;

		$this->timeout = (int) $timeout;

		$this->flags = $flags;
	}

	public function __call($method, array $arguments)
	{
		$key = $method . '_' . $this->_hash($arguments);

		$value = $this->cache->get($key, null);

		if ($value !== null)
			return $value;

		if ($this->flags & self::CATCH_EXCEPTION)
			$value = $this->_call_method_safe($method, $arguments);
		else
			$value = $this->_call_method($method, $arguments);

		$this->cache->put($key, $value, $this->timeout);

		return $value;
	}

	private function _call_method_safe($method, array $arguments)
	{
		try {
			return $this->_call_method($method, $arguments);
		} catch (Exception $e) {
			return null;
		}
	}

	private function _call_method($method, array $arguments)
	{
		return call_user_func_array(array($this->delegate, $method), $arguments);
	}

	private function _hash($arguments)
	{
		if (is_array($arguments))
			return implode('_', array_map(array($this, '_hash'), $arguments));
		elseif (is_object($arguments))
			return get_class($arguments);
		elseif (is_int($arguments) || is_string($arguments) || is_float($arguments))
			return strval($arguments);
		elseif (is_bool($arguments))
			return $arguments ? 't' : 'f';
		else
			return '0';
	}
}
