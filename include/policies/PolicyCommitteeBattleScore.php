<?php

class PolicyCommitteeBattleScore implements Policy
{
	public function user_can_create()
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR);
	}

	public function user_can_read(DataIter $board)
	{
		return true;
	}

	public function user_can_update(DataIter $board)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR);
	}

	public function user_can_delete(DataIter $board)
	{
		return $this->user_can_update($board);
	}
}