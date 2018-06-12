<?php

require_once 'include/member.php';

class PolicySignUpForm implements Policy
{
	static private $pilot_committees = [COMMISSIE_BESTUUR, 2]; // Activitee

	public function user_can_create(DataIter $form)
	{
		// BEGIN Trial period
		if (!array_one(self::$pilot_committees, [get_identity(), 'member_in_committee']))
			return false;

		if ($form['committee_id'] !== null && !in_array($form['committee_id'], self::$pilot_committees))
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
