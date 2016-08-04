<?php

require_once 'include/member.php';

class PolicyEditable implements Policy
{
	public function user_can_create()
	{
		return member_in_commissie(COMMISSIE_BESTUUR);
	}

	public function user_can_read(DataIter $editable)
	{
		return true;
	}

	public function user_can_update(DataIter $editable)
	{
		return member_in_commissie($editable->get('owner')) || member_in_commissie(COMMISSIE_BESTUUR);
	}

	public function user_can_delete(DataIter $editable)
	{
		return member_in_commissie(COMMISSIE_BESTUUR);
	}
}
