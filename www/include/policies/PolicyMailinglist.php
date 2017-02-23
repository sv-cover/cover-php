<?php

class PolicyMailinglist implements Policy
{
	public function user_can_create(DataIter $iter)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function user_can_read(DataIter $iter)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY)
			|| get_identity()->member_in_committee($iter['commissie']);
	}

	public function user_can_update(DataIter $iter)
	{
		return $this->user_can_read($iter);
	}

	public function user_can_delete(DataIter $iter)
	{
		return $this->user_can_read($iter);
	}

	public function user_can_subscribe(DataIterMailinglist $lijst)
	{
		if ($this->user_can_update($lijst))
			return true;

		// You cannot subscribe yourself to a non-public list
		if (!$lijst['publiek'])
			return false;

		// You cannot 'subscribe' (opt back in) to an opt-out list if you are not a member
		if ($lijst['type'] == DataModelMailinglist::TYPE_OPT_OUT && get_identity()->get('type') != MEMBER_STATUS_LID)
			return false;

		return true;
	}

	public function user_can_unsubscribe(DataIterMailinglist $lijst)
	{
		if ($this->user_can_update($lijst))
			return true;

		// You cannot unsubscribe from non-public lists
		if (!$lijst['publiek'])
			return false;

		// Any other list is perfectly fine.
		return true;
	}
}