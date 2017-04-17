<?php

class DataModelCommitteeMascot
{
	private $mascots;

	public function __construct($db)
	{
		$this->mascots = [
			'herocee' => [
				[
					'name' => 'Koda',
					'functie' => 'Care bear',
					'photo' => 'images/mascots/koda.jpg'
				]
			]
		];
	}

	public function find_for_committee(DataIterCommissie $committee)
	{
		return isset($this->mascots[$committee['login']])
			? $this->mascots[$committee['login']]
			: [];
	}
}
