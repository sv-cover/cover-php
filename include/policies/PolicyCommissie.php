<?php

require_once 'include/member.php';

class PolicyCommissie implements Policy
{
	public function user_can_create()
	{
		return member_in_commissie(COMMISSIE_BESTUUR);
	}

	public function user_can_read(DataIter $committee)
	{
		return true;
	}

	public function user_can_update(DataIter $committee)
	{
		return $committee->has_id() && member_in_commissie(COMMISSIE_BESTUUR);
	}

	public function user_can_delete(DataIter $committee)
	{
		return $committee->has_id() && member_in_commissie(COMMISSIE_BESTUUR);
	}
}
