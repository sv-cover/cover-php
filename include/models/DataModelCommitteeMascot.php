<?php

class DataModelCommitteeMascot
{
	private $mascots;

	public function __construct($db)
	{
		try {
			$data = file_get_contents('images/mascots/data.json');
			$this->mascots = json_decode($data, true);
		} catch (Exception $e) {
			$this->mascots = [];
		}
	}

	public function find_for_committee(DataIterCommissie $committee)
	{
		return isset($this->mascots[$committee['login']])
			? $this->mascots[$committee['login']]
			: [];
	}
}
