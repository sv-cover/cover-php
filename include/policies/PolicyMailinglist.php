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
		if (!get_auth()->logged_in())
			return false;

		// You cannot subscribe yourself to a non-public list
		if (!$lijst['publiek'])
			return false;

		// You cannot subscribe to a list (or opt back in to an opt-out list) that doesn't accept your type
		if (!($lijst['has_members'] && get_identity()->is_member())
			&& !($lijst['has_contributors'] && get_identity()->is_donor()))
			return false;

		// You cannot subscribe to a list that is targeted at a starting year that's not yours
		if (!empty($lijst['has_starting_year']) && get_identity()->get('beginjaar') != $lijst['has_starting_year'])
			return false;

		return true;
	}

	public function user_can_unsubscribe(DataIterMailinglist $lijst)
	{
		// You cannot unsubscribe from non-public lists
		if (!$lijst['publiek'])
			return false;

		// Any other list is perfectly fine.
		return true;
	}
}