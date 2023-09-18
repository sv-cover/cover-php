<?php

require_once 'src/framework/auth.php';
require_once 'src/models/DataModelNewPoll.php';

class PolicyNewPoll implements Policy
{
	public function user_can_create(DataIter $poll)
	{
		if (!get_auth()->logged_in())
			return false;
		return true;
		// At least 7 days after the current poll, unless the current poll was the same author. Then it's 14 days.
		// return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
		// 	|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function user_can_read(DataIter $poll)
	{
		return true;
	}

	public function user_can_update(DataIter $poll)
	{
		return false;
	}

	public function user_can_delete(DataIter $poll)
	{
		if (!get_auth()->logged_in())
			return false;

		// User owns it or board/acdcee
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY)
			|| (isset($poll['committee']) && get_identity()->member_in_committee($poll['committee_id']))
			|| (!isset($poll['committee']) && get_identity()->get('id') == $poll['member_id'])
		;
	}

	public function user_can_vote(DataIter $poll)
	{
		if (!get_auth()->logged_in())
			return false;

		return get_auth()->logged_in()
			&& $this->user_can_read($poll)
			&& $poll['is_open']
			&& !$poll->get_member_has_voted(get_identity()->member())
		;
	}

	public function user_can_close(DataIter $poll)
	{
		return $this->user_can_delete($poll);
	}

	public function user_can_reopen(DataIter $poll)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}
}
