<?php
require_once 'src/policies/PolicyForumAbstract.php';

class PolicyForumThread extends PolicyForumAbstract
{
	public function user_can_create(DataIter $thread)
	{
		return false;
		if (!get_auth()->logged_in())
			return false;
		
		if ($this->member_is_admin())
			return true;

		return $this->model->check_acl($thread['forum'], DataModelForum::ACL_WRITE, get_identity());
	}	

	public function user_can_read(DataIter $thread)
	{
		if ($this->member_is_admin())
			return true;
		
		return $this->model->check_acl($thread['forum'], DataModelForum::ACL_READ, get_identity());
	}

	public function user_can_update(DataIter $thread)
	{
		if (!get_auth()->logged_in())
			return false;
		
		if ($this->member_is_admin())
			return true;

		return $thread->is_author(get_identity());
	}

	public function user_can_delete(DataIter $thread)
	{
		return $this->user_can_update($thread);
	}
}