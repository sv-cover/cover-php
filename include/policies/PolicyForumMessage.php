<?php
require_once 'include/policies/PolicyForumAbstract.php';

class PolicyForumMessage extends PolicyForumAbstract
{
	public function user_can_create(DataIter $message)
	{
		// Can I create a new message in a thread, or in other words, can I reply
		// to a thread? Well, that depends on the access rights you have to a
		// specific forum!

		$thread = $this->model->get_thread($message['thread']);

		$forum = $this->model->get_iter($thread['forum']);

		return $this->model->check_acl($forum, DataModelForum::ACL_REPLY, get_identity());
	}

	public function user_can_read(DataIter $message)
	{
		// Reading again depends on the forum access rights. Yes, even when it is
		// your own message!

		$thread = $this->model->get_thread($message['thread']);

		$forum = $this->model->get_iter($thread['forum']);

		return $this->model->check_acl($forum, DataModelForum::ACL_READ, get_identity());
	}

	public function user_can_update(DataIter $message)
	{
		// Users can update their own messages

		if (!get_auth()->logged_in())
			return false;
		
		if ($this->member_is_admin())
			return true;
		
		switch ($message['author_type'])
		{
			case DataModelForum::TYPE_PERSON: /* Person */
				return $message['author'] == get_identity()->get('id');
			break;
			case DataModelForum::TYPE_COMMITTEE: /* Commissie */
				return get_identity()->member_in_committee($message['author']);
			break;
		}

		return false;
	}

	public function user_can_delete(DataIter $message)
	{
		// You cannot simply delete the first message, because that means deleting a thread.
		if ($message->is_first_message()) {
			$thread = $this->model->get_thread($message['thread']);
			return get_policy($thread)->user_can_delete($thread);
		}
		
		return $this->user_can_update($message);
	}
}