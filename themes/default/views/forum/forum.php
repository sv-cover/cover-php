<?php

class ForumView extends View
{
	public function stylesheets()
	{
		return array_merge(parent::stylesheets(), [
			get_theme_data('styles/forum.css')
		]);
	}
	
	public function render_thread(DataIterForumThread $thread, $params)
	{
		$model = get_model('DataModelForum');

		$forum = $model->get($thread['forum'], -1);

		$page = isset($params['page']) ? $params['page'] : 0;

		$messages = $thread->get_messages($page, $max);

		$can_reply = $model->check_acl($thread['forum'], DataModelForum::ACL_REPLY);

		$can_delete = get_identity()->member_in_committee(COMMISSIE_BESTUUR)
				   || get_identity()->member_in_committee(COMMISSIE_EASY);

		return $this->twig->render('thread.twig', compact('thread', 'forum', 'page', 'messages', 'can_reply', 'can_delete', 'max'));
	}

	public function render_index($iters)
	{
		$model = get_model('DataModelForum');

		$headers = $model->get_headers();

		return $this->twig->render('index.twig', compact('iters', 'headers'));
	}

	public function get_authors(DataIterForum $forum, $acl)
	{
		$model = get_model('DataModelForum');

		$authors = array();
		$member_data = logged_in();
		$authors[-1] = member_full_name();

		$commissie_model = get_model('DataModelCommissie');

		foreach ($member_data['committees'] as $commissie) {
			if ($model->check_acl_commissie($forum['id'], $acl, $commissie))
				$authors[$commissie] = $commissie_model->get_naam($commissie);
		}
		
		return $authors;
	}

	public function get_author_link(DataIterForumMessage $message, $last = false)
	{
		if ($last && $message['last_author_type'])
			$field = 'last_author';
		else
			$field = 'author';

		switch (intval($message[$field . '_type']))
		{
			case DataModelForum::TYPE_PERSON: /* Person */
				return 'profiel.php?lid=' . $message[$field];
			
			case DataModelForum::TYPE_COMMITTEE: /* Commissie */
				$committee_model = get_model('DataModelCommissie');
				$committee = $committee_model->get_iter($message[$field]);
				return 'commissies.php?commissie=' . urlencode($committee['login']);
			
			default:
				return null;
		}
	}
}