<?php
require_once 'include/policies/PolicyForumAbstract.php';

class PolicyForum extends PolicyForumAbstract
{
	public function user_can_create(DataIter $forum)
	{
		return $this->member_is_admin();
	}	

	public function user_can_read(DataIter $forum)
	{
		return $this->member_is_admin() || $this->model->check_acl($forum, DataModelForum::ACL_READ, get_identity());
	}

	public function user_can_update(DataIter $forum)
	{
		return $this->member_is_admin();
	}

	public function user_can_delete(DataIter $forum)
	{
		return $this->member_is_admin();
	}
}