<?php
require_once 'include/policies/PolicyForumThread.php';

class PolicyPoll extends PolicyForumThread
{
	public function user_can_create(DataIter $poll)
	{
		if ($this->member_is_admin())
			return true;
		
		$forum = $this->model->get_iter($poll['forum']);

		return $this->model->check_acl($forum, DataModelForum::ACL_POLL, get_identity());
	}
}