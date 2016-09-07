<?php

require_once 'include/member.php';

class PolicyCommissie implements Policy
{
	public function user_can_create()
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);
	}

	public function user_can_read(DataIter $committee)
	{
		return true;
	}

	public function user_can_update(DataIter $committee)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);
	}

	public function user_can_delete(DataIter $committee)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);
	}
}
