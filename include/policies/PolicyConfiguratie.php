<?php

class PolicyConfiguratie implements Policy
{
	public function user_can_create()
	{
		return get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function user_can_read(DataIter $entry)
	{
		return get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function user_can_update(DataIter $entry)
	{
		return get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function user_can_delete(DataIter $entry)
	{
		return get_identity()->member_in_committee(COMMISSIE_EASY);
	}
}
