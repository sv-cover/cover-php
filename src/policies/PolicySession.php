<?php

require_once 'src/framework/auth.php';

class PolicySession implements Policy
{
	public function user_can_create(DataIter $session)
	{
		return false;
	}

	public function user_can_read(DataIter $session)
	{
		// You can see your own sessions
		if ($session['member_id'] == get_identity()->get('id'))
			return true;

		// WebCie can see all sessions
		return get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function user_can_update(DataIter $session)
	{
		return false;
	}

	public function user_can_delete(DataIter $session)
	{
		return $this->user_can_update($session);
	}
}
