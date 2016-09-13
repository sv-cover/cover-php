<?php

/**
 * This is a bit of a utility class for all forum related policies.
 */
abstract class PolicyForumAbstract implements Policy
{
	protected $model;
	
	public function __construct()
	{
		$this->model = get_model('DataModelForum');
	}

	protected function member_is_admin()
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}
}