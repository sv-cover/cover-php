<?php

require_once 'include/member.php';

class PolicySignUpForm implements Policy
{
	public function user_can_create(DataIter $form)
	{
		// BEGIN Trial period, only for activitee right now
		if (!get_identity()->member_in_committee(2)) // Activitee
			return false;

		if ($form['committee_id'] !== null && $form['committee_id'] != 2)
			return false;
		// END

		if ($form['committee_id'] !== null)
			return get_identity()->member_in_committee($form['committee_id']);
		else
			return get_identity()->member_in_committee();
	}

	public function user_can_read(DataIter $form)
	{
		return get_identity()->member_is_active();
	}

	public function user_can_update(DataIter $form)
	{
		return get_identity()->member_in_committee($form['committee_id']);
	}

	public function user_can_delete(DataIter $form)
	{
		return get_identity()->member_in_committee($form['committee_id']);
	}
}
