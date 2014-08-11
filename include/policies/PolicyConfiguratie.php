<?php

include 'include/member.php';

class PolicyConfiguratie implements Policy
{
	public function user_can_create()
	{
		return member_in_commissie(COMMISSIE_EASY);
	}

	public function user_can_read(DataIter $entry)
	{
		return member_in_commissie(COMMISSIE_EASY);
	}

	public function user_can_update(DataIter $entry)
	{
		return member_in_commissie(COMMISSIE_EASY);
	}

	public function user_can_delete(DataIter $entry)
	{
		return member_in_commissie(COMMISSIE_EASY);
	}
}