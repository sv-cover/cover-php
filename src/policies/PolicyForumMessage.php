<?php
require_once 'src/policies/PolicyForumAbstract.php';

class PolicyForumMessage extends PolicyForumAbstract
{
	public function user_can_create(DataIter $message)
	{
		// First of all, you need to be logged in
		if (!get_auth()->logged_in())
			return false;

		if ($this->member_is_admin())
			return true;
		
		// Can I create a new message in a thread, or in other words, can I reply
		// to a thread? Well, that depends on the access rights you have to a
		// specific forum!
		return $this->model->check_acl($message['thread']['forum'], DataModelForum::ACL_REPLY, get_identity());
	}

	public function user_can_read(DataIter $message)
	{
		return get_policy($message['thread'])->user_can_read($message['thread']);
	}

	public function user_can_update(DataIter $message)
	{
		// Users can update their own messages

		if (!get_auth()->logged_in())
			return false;
		
		if ($this->member_is_admin())
			return true;
		
		return $message->is_author(get_identity());
	}

	public function user_can_delete(DataIter $message)
	{
		// You cannot simply delete the only message, because that means deleting a thread.
		if ($message->is_only_message())
			return get_policy($message['thread'])->user_can_delete($message['thread']);
		
		return $this->user_can_update($message);
	}
}