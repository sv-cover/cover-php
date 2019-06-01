<?php

class PolicyCommitteeBattleScore implements Policy
{
	public function user_can_create(DataIter $iter)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR);
	}

	public function user_can_read(DataIter $iter)
	{
		return true;
	}

	public function user_can_update(DataIter $iter)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR);
	}

	public function user_can_delete(DataIter $iter)
	{
		return $this->user_can_update($iter);
	}
}