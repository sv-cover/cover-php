<?php

require_once 'include/member.php';

class PolicyAgenda implements Policy
{
	public function user_can_create(DataIter $agenda_item)
	{
		// Anyone who is in a committee can create agenda items (for said committee)
		return get_identity()->member_in_committee();
	}

	public function user_can_read(DataIter $agenda_item)
	{
		// Only board, candidate board, and the creators of new agenda items can
		// read them when they are not yet confirmed by the board.
		if ($agenda_item->is_proposal())
			return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
				|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
				|| get_identity()->member_in_committee($agenda_item->get('commissie'));

		// Private agenda items can only be seen by active members
		if ($agenda_item['private'])
			return get_identity()->member_is_active();

		// By default all agenda items are accessible
		return true;
	}

	public function user_can_update(DataIter $agenda_item)
	{
		// Proposals cannot be modified (but their original version can be!)
		if ($agenda_item->is_proposal())
			return false;

		// Board and candidate board can always update agenda items
		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) || get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			return true;

		// And committee members may update their own agenda items of course
		if (get_identity()->member_in_committee($agenda_item->get('commissie')))
			return true;

		return false;
	}

	public function user_can_delete(DataIter $agenda_item)
	{
		return $this->user_can_update($agenda_item);
	}

	public function user_can_moderate(DataIter $agenda_item)
	{
		// Only proposals can be moderated
		if (!$agenda_item->is_proposal())
			return false; 

		// And only board and candidate board may moderate
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR);
	}
}
