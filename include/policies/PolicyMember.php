<?php

class PolicyMember implements Policy
{
	public function user_can_create()
	{
		// Nobody can create except for the API, which is called by Secretary.
		return false;
	}

	public function user_can_read(DataIter $iter)
	{
		// Only 
		if ($iter->get('type') == MEMBER_STATUS_LID_AF)
			return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
				|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);

		return true;
	}

	public function user_can_update(DataIter $iter)
	{
		// Members who are not no-member can update themselves
		if ($iter->get('type') != MEMBER_STATUS_LID_AF
			&& $iter->get('id') == get_identity()->get('id'))
			return true;

		// Board and candidate board can update anyone
		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			return true;

		// And that's it.
		return false;
	}

	public function user_can_delete(DataIter $iter)
	{
		// Nobody can delete, because that is untested behaviour.
		return false;
	}
}
