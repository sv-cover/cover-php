<?php

require_once 'include/member.php';

class PolicySignUpForm implements Policy
{

	public function user_can_create(DataIter $form)
	{
		if ($form['committee_id'] !== null)
			return get_identity()->member_in_committee($form['committee_id']);
		else
			return get_identity()->member_in_committee();
	}

	public function user_can_read(DataIter $form)
	{
		return get_identity()->is_member() || get_identity()->is_donor();
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
