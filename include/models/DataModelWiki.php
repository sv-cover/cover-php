<?php

class WikiRemoteCallException extends Exception
{
	//
}

class DataIterWiki extends DataIter implements SearchResult
{
	static public function fields()
	{
		return [];
	}

	public function get_search_relevance()
	{
		return normalize_search_rank($this->get('score'));
	}

	public function get_search_type()
	{
		return 'wiki';
	}

	public function get_absolute_url()
	{
		return sprintf(get_config_value('wiki_public_url'), $this->get('id'));
	}
}

class DataModelWiki implements SearchProvider
{
	private $_wiki_url;

	public function __construct($db)
	{
		$this->_wiki_url = get_config_value('wiki_url');
	}

	public function isConfigured()
	{
		return $this->_wiki_url != null;
	}

	public function search($query, $limit = null)
	{
		try {
			if (!$this->isConfigured())
				throw new WikiRemoteCallException('Wiki data model is not configured', 1);
		
			$results = $this->_call('dokuwiki.search', [$query]);
		
			$iters = [];

			foreach ($results as $result)
				$iters[] = new DataIterWiki(null, $result['id'], $result);

			return $iters;
		} catch (WikiRemoteCallException $e) {
			return [];
		}
	}

	private function _encodeMethodCall($method, array $args)
	{
		$body = new DOMDocument('1.0', 'UTF-8');
		$methodCall = $body->createElement('methodCall');
		$body->appendChild($methodCall);

		$methodName = $body->createElement('methodName', $method);
		$methodCall->appendChild($methodName);

		$params = $body->createElement('params');
		$methodCall->appendChild($params);

		foreach ($args as $arg) {
			$param = $body->createElement('param');
			$params->appendChild($param);

			$value = $body->createElement('value');
			$param->appendChild($value);

			if (is_int($arg))
				$value->appendChild($body->createElement('i4', $arg));
			elseif (is_string($arg))
				$value->appendChild($body->createElement('string', $arg));
			else
				throw new Exception('Sorry, not implemented');
		}

		return $body->saveXML();
	}

	private function _decodeMethodResponse($xml)
	{
		try {
			$doc = new SimpleXMLElement($xml);

			$value = $doc->params[0]->param[0]->value[0]->children()[0];

			return $this->_decodeValue($value);
		} catch (Exception $e) {
			throw new WikiRemoteCallException('Could not decode response', 2, $e);
		}
	}

	private function _decodeValue(SimpleXMLElement $element)
	{
		switch ($element->getName())
		{
			case 'array':
				return $this->_decodeArray($element);

			case 'struct':
				return $this->_decodeStruct($element);

			case 'string':
				return $this->_decodeString($element);

			case 'int':
				return $this->_decodeInt($element);

			default:
				throw new RuntimeException('Not implemented: ' . $element->getName());
		}
	}

	private function _decodeArray(SimpleXMLElement $element)
	{
		$elements = [];

		foreach ($element->data[0]->value as $value)
			$elements[] = $this->_decodeValue($value->children()[0]);

		return $elements;
	}

	private function _decodeStruct(SimpleXMLElement $element)
	{
		$data = [];

		foreach ($element->member as $member)
		{
			$name = strval($member->name[0]);
			$value = $this->_decodeValue($member->value[0]->children()[0]);
			$data[$name] = $value;
		}

		return $data;
	}

	private function _decodeString(SimpleXMLElement $element)
	{
		return strval($element);
	}

	private function _decodeInt(SimpleXMLElement $element)
	{
		return intval(strval($element));
	}

	private function _call($method, array $args)
	{
		$body = $this->_encodeMethodCall($method, $args);

		$headers = ['Content-Type: text/xml'];

		// If we are logged in, carry that session over to the wiki to access restricted pages
		if (get_auth()->get_session())
			$headers[] = sprintf('Cookie: cover_session_id=%s', get_auth()->get_session()->get('session_id'));

		$opts = [
			'http' => [
				'method' => 'POST',
				'header' => implode("\r\n", $headers),
				'content' => $body
			]
		];

		$context = stream_context_create($opts);

		$response = file_get_contents($this->_wiki_url, false, $context);

		return $this->_decodeMethodResponse($response);
	}
}
