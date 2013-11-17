<?php

class Document
{
	public $header;

	public $body;

	public $footer;

	public $container = '%s%s%s';

	public function __toString()
	{
		return sprintf($this->container, $this->header, $this->body, $this->footer);
	}
}