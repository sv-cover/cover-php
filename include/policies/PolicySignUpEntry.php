<?php

require_once 'include/member.php';

class PolicySignUpEntry implements Policy
{
	public function user_can_create(DataIter $entry)
	{
		return get_identity()->member_is_active();
	}

	public function user_can_read(DataIter $entry)
	{
		return get_identity()->get('id') === $entry['member_id']
			|| get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee($entry['form']['committee_id']);
	}

	public function user_can_update(DataIter $entry)
	{
		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR))
			return true;

		if (get_identity()->member_in_committee($entry['form']['committee_id']))
			return true;

		if ($entry['form']->is_open() && get_identity()->get('id') === $entry['member_id'])
			return true;
		
		return false;
	}

	public function user_can_delete(DataIter $entry)
	{
		return get_identity()->member_in_committee($entry['form']['committee_id']);
	}
}
