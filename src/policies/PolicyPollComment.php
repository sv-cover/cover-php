<?php

require_once 'src/framework/auth.php';
require_once 'src/models/DataModelPoll.php';

class PolicyPollComment implements Policy
{
	public function user_can_create(DataIter $comment)
	{
		return get_auth()->logged_in();
	}

	public function user_can_read(DataIter $comment)
	{
		return true;
	}

	public function user_can_update(DataIter $comment)
	{
		if (!get_auth()->logged_in())
			return false;

		// User owns it or board/acdcee
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY)
			|| get_identity()->get('id') == $comment['member_id']
		;
	}

	public function user_can_delete(DataIter $comment)
	{
		return $this->user_can_update($comment);
	}

	public function user_can_like(DataIter $comment)
	{
		return get_auth()->logged_in();
	}
}
