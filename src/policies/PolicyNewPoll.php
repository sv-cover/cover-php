<?php

require_once 'src/framework/auth.php';
require_once 'src/models/DataModelNewPoll.php';

class PolicyNewPoll implements Policy
{
	public function user_can_create(DataIter $poll)
	{
		if (!get_auth()->logged_in())
			return false;

		$current_poll = get_model('DataModelNewPoll')->get_current();
		if (!$current_poll)
			return true;

		// Members always have to wait 14 days between creating polls
		if ($poll['committee_id'] === null && get_identity()->get('id') == $current_poll->member_id)
			return new \DateTime($current_poll['created_on']) < new \DateTime("-14 days");

		// If you didn't create the last poll, you'll have to wait untill it's closed or at least 7 days old
		return !$current_poll['is_open']
			|| new \DateTime($current_poll['created_on']) < new \DateTime("-7 days");
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
			|| ($poll['committee_id'] !== null && get_identity()->member_in_committee($poll['committee_id']))
			|| ($poll['committee_id'] === null && get_identity()->get('id') == $poll['member_id'])
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

	public function user_can_like(DataIter $poll)
	{
		return get_auth()->logged_in();
	}
}
