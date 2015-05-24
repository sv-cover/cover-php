<?php

class JSONWriter
{
	const IN_ARRAY = 1;
	const IN_OBJECT = 2;

	const PRETTY_PRINT = 1;

	private $_stack;

	private $_hasWrittenKey = false;

	private $_isFirstElement = true;

	private $_stream;

	private $_options;

	public function __construct($stream, $options = 0)
	{
		$this->_stack = new SplStack();
		$this->_stream = $stream;
		$this->_options = $options;
	}

	public function inArray()
	{
		return !$this->_stack->isEmpty()
			&& $this->_stack->top() == self::IN_ARRAY;
	}

	public function inObject()
	{
		return !$this->_stack->isEmpty()
			&& $this->_stack->top() == self::IN_OBJECT;
	}

	public function startArray()
	{
		if ($this->inObject() && !$this->_hasWrittenKey)
			throw new RuntimeException('Key not yet written');

		if ($this->inArray() && !$this->_isFirstElement)
			$this->_writeSeparator();

		if ($this->_options & self::PRETTY_PRINT && !$this->_hasWrittenKey)
			$this->_write("\n" . $this->_getIndent());

		$this->_write('[');
		$this->_stack->push(self::IN_ARRAY);
		$this->_hasWrittenKey = false;
		$this->_isFirstElement = true;

		return $this;
	}

	public function startObject()
	{
		if ($this->inObject() && !$this->_hasWrittenKey)
			throw new RuntimeException('Key not yet written');

		if ($this->inArray() && !$this->_isFirstElement)
			$this->_writeSeparator();

		if ($this->_options & self::PRETTY_PRINT && !$this->_hasWrittenKey)
			$this->_write("\n" . $this->_getIndent());

		$this->_write('{');
		$this->_stack->push(self::IN_OBJECT);
		$this->_hasWrittenKey = false;
		$this->_isFirstElement = true;

		return $this;
	}

	public function endArray()
	{
		if (!$this->inArray())
			throw new RuntimeException('There is no open array');

		if ($this->_options & self::PRETTY_PRINT)
			$this->_write("\n" . $this->_getIndent(-1));

		$this->_write(']');
		$this->_stack->pop();
		$this->_isFirstElement = false;

		return $this;
	}

	public function endObject()
	{
		if (!$this->inObject())
			throw new RuntimeException('There is no open object');

		if ($this->_hasWrittenKey)
			throw new RuntimeException('There is still an open key without value');

		if ($this->_options & self::PRETTY_PRINT)
			$this->_write("\n" . $this->_getIndent(-1));

		$this->_write('}');
		$this->_stack->pop();
		$this->_isFirstElement = false;

		return $this;
	}

	public function key($key)
	{
		if (!$this->inObject())
			throw new RuntimeException('There is no open object');

		if ($this->_hasWrittenKey)
			throw new RuntimeException('Key already written');

		if (!$this->_isFirstElement)
			$this->_writeSeparator();

		if ($this->_options & self::PRETTY_PRINT && !$this->_hasWrittenKey)
			$this->_write("\n" . $this->_getIndent());

		$this->_write('"' . addslashes($key) . '"');
		$this->_write($this->_options & self::PRETTY_PRINT ? ': ': ':');
		$this->_hasWrittenKey = true;

		return $this;
	}

	public function value($value)
	{
		if ($this->inObject() && !$this->_hasWrittenKey)
			throw new RuntimeException('Key not yet written');

		if (!$this->inObject() && !$this->inArray() && !$this->_isFirstElement)
			throw new RuntimeException('Data has already been printed');
		
		if ($this->inArray() && !$this->_isFirstElement)
			$this->_writeSeparator();

		if ($this->_options & self::PRETTY_PRINT && !$this->_hasWrittenKey)
			$this->_write("\n" . $this->_getIndent());

		if (is_int($value) || is_float($value))
			$this->_write($value);
		elseif (is_bool($value))
			$this->_write($value ? 'true' : 'false');
		elseif (is_null($value))
			$this->_write('null');
		elseif (is_string($value))
			$this->_write('"' . addslashes($value) . '"');
		else
			$this->_write(json_encode($value));

		$this->_isFirstElement = false;
		$this->_hasWrittenKey = false;

		return $this;
	}

	public function close()
	{
		if ($this->_hasWrittenKey)
			$this->_write('null');

		while (!$this->_stack->isEmpty())
		{
			switch ($this->_stack->top())
			{
				case self::IN_ARRAY:
					$this->endArray();
					break;

				case self::IN_OBJECT:
					$this->endObject();
					break;
			}
		}

		return $this;
	}

	public function isClosed()
	{
		return $this->_stack->isEmpty()
			&& !$this->_isFirstElement;
	}

	protected function _getIndent($delta = 0)
	{
		return str_repeat("\t", count($this->_stack) + $delta);
	}

	protected function _writeSeparator()
	{
		$this->_write(',');
	}

	protected function _write($data)
	{
		fwrite($this->_stream, $data);
	}
}