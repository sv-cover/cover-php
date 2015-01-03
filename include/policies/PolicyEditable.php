<?php

require_once 'include/member.php';

class PolicyEditable implements Policy
{
	public function user_can_create()
	{
		return member_in_commissie();
	}

	public function user_can_read(DataIter $photo)
	{
		return true;
	}

	public function user_can_update(DataIter $iter)
	{
		return member_in_commissie($iter->get('owner')) || member_in_commissie(COMMISSIE_BESTUUR);
	}

	public function user_can_delete(DataIter $photo)
	{
		return $this->user_can_update($iter);
	}
	
}
