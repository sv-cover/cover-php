<?php
require_once 'include/policies/PolicyForumAbstract.php';

class PolicyForumThread extends PolicyForumAbstract
{
	public function user_can_create(DataIter $thread)
	{
		if ($this->member_is_admin())
			return true;
		
		$forum = $this->model->get_iter($thread['forum']);

		return $this->model->check_acl($forum, DataModelForum::ACL_WRITE, get_identity());
	}	

	public function user_can_read(DataIter $thread)
	{
		if ($this->member_is_admin())
			return true;
		
		$forum = $this->model->get_iter($thread['forum']);

		return $this->model->check_acl($forum, DataModelForum::ACL_READ, get_identity());
	}

	public function user_can_update(DataIter $thread)
	{
		if (!get_auth()->logged_in())
			return false;
		
		if ($this->member_is_admin())
			return true;
		
		switch ($thread['author_type'])
		{
			case DataModelForum::TYPE_PERSON: /* Person */
				return $thread['author'] == get_identity()->get('id');
			break;
			case DataModelForum::TYPE_COMMITTEE: /* Commissie */
				return get_identity()->member_in_committee($thread['author']);
			break;
		}

		return false;
	}

	public function user_can_delete(DataIter $thread)
	{
		return $this->member_is_admin();
	}
}