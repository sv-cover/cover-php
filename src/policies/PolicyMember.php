<?php

class PolicyMember implements Policy
{
	public function user_can_create(DataIter $iter)
	{
		// Nobody can create except for the API, which is called by Secretary.
		return false;
	}

	public function user_can_read(DataIter $iter)
	{
		// You can see yourself
		if ($iter['id'] == get_identity()->get('id'))
			return true;

		// You can see members, honourary members and donors
		if (in_array($iter['type'], [MEMBER_STATUS_LID, MEMBER_STATUS_ERELID, MEMBER_STATUS_DONATEUR]))
			return true;
		
		// And only the board can see the rest.
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);
	}

	public function user_can_update(DataIter $iter)
	{
		if ($iter['id'] == get_identity()->get('id'))
			return true;

		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function user_can_delete(DataIter $iter)
	{
		// Nobody can delete, because that is untested behaviour.
		return false;
	}
}
