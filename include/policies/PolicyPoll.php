<?php
require_once 'include/policies/PolicyForumThread.php';

class PolicyPoll extends PolicyForumThread
{
	public function user_can_create(DataIter $poll)
	{
		if ($this->member_is_admin())
			return true;
		
		$forum = $this->model->get_iter($poll['forum_id']);

		return $this->model->check_acl($forum, DataModelForum::ACL_POLL, get_identity());
	}

	public function user_can_vote(DataIter $poll)
	{
		return $this->user_can_read($poll)
			&& !$poll->member_has_voted(get_identity()->member());
	}
}