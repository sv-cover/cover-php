<?php

require_once 'include/member.php';

class PolicySignUpEntry implements Policy
{
	public function user_can_create(DataIter $entry)
	{
		// Active members can sign up if it is open
		if ($entry['form']->is_open())
			return get_identity()->member_is_active(); 
		
		// The committee of the activity can always add people to the activity
		if (get_identity()->member_in_committee($entry['form']['committee_id']))
			return true;

		return false;
	}

	public function user_can_read(DataIter $entry)
	{
		// Board can read & update them
		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) || get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			return true;

		// and of course the committee of the form can
		if (get_identity()->member_in_committee($entry['form']['committee_id']))
			return true;

		// The member of the entry can read their own entries
		if (get_identity()->get('id') === $entry['member_id'])
			return $entry['form']->is_open();
		
		return false;
	}

	public function user_can_update(DataIter $entry)
	{
		// Board can read & update them
		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) || get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			return true;

		// and of course the committee of the form can
		if (get_identity()->member_in_committee($entry['form']['committee_id']))
			return true;

		// The member of the entry can read their own entries
		if (get_identity()->get('id') === $entry['member_id'])
			return $entry['form']->is_open();
		
		return false;
	}

	public function user_can_delete(DataIter $entry)
	{
		// Only the board and the committee can delete entries. You cannot "just" delete your own entry.
		if (get_identity()->member_in_committee($entry['form']['committee_id']))
			return true;

		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) || get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			return true;

		return false;
	}
}
