<?php

require_once 'include/member.php';

class PolicyBesturen implements Policy
{
	public function user_can_create()
	{
		return member_in_commissie(COMMISSIE_BESTUUR);
	}

	public function user_can_read(DataIter $board)
	{
		return true;
	}

	public function user_can_update(DataIter $board)
	{
		return member_in_commissie(COMMISSIE_BESTUUR);
	}

	public function user_can_delete(DataIter $board)
	{
		return $this->user_can_update($board);
	}
}
