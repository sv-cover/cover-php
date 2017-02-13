<?php

require_once 'include/member.php';

class PolicyEditable implements Policy
{
	public function user_can_create(DataIter $editable)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);
	}

	public function user_can_read(DataIter $editable)
	{
		return true;
	}

	public function user_can_update(DataIter $editable)
	{
		// TODO: maybe its time for a more advanced access level here than just
		// ownership. Because for example the editables that are used by
		// the committee pages should be editable by the committee members
		// (which works right now because the committees are the owner)
		// but pages such as study information could also be editable by members
		// of both the BookCee, StudCee, and other study-related groups?
		return get_identity()->member_in_committee($editable['owner'])
			|| get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);
	}

	public function user_can_delete(DataIter $editable)
	{
		// (I don't trust the candidate board enough yet to give them destructive powers!)
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR);
	}
}
