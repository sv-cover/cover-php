<?php

class ForumView extends View
{
	public function stylesheets()
	{
		return array_merge(parent::stylesheets(), [
			get_theme_data('styles/forum.css')
		]);
	}

	public function render_index($iters)
	{
		$model = get_model('DataModelForum');

		$headers = $model->get_headers();

		return $this->twig->render('index.twig', compact('iters', 'headers'));
	}

	public function render_forum(DataIterForum $forum, $params)
	{
		$model = get_model('DataModelForum');

		$page = isset($params['page']) ? $params['page'] : 0;
		
		$threads = $model->get_threads($forum, $page, $max);

		$can_create_topic = $model->check_acl($forum['id'], DataModelForum::ACL_WRITE);
		
		$can_create_poll = $model->check_acl($forum['id'], DataModelForum::ACL_POLL);

		return $this->twig->render('forum.twig', compact('forum', 'params', 'page', 'max', 'threads', 'can_create_topic', 'can_create_poll'));
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

	public function get_author_link(DataIter $message, $last = false)
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

	public function page_navigation($url, $current, $max, $nav_num = 10)
	{
		$nav = '<div class="page_navigation">' . __('Ga naar pagina') . ': ';

		if ($current != 0)
			$nav .= '<a href="' . add_request($url, 'page=' . ($current - 1)) . '">' . image('previous.png', __('vorige'), __('Vorige pagina') . '</a>');
		
		$nav_min = max(0, $current - ($nav_num / 2));
		$nav_max = min($max, $current + ($nav_num / 2) - 1);
		
		if ($nav_max - $nav_min < $nav_num)
			$nav_max = min($max, $nav_min + $nav_num - 1);
		
		for ($i = $nav_min; $i <= $nav_max; $i++) {
			if ($i == $current)
				$nav .= '<span class="bold">' . ($i + 1) . '</span> ';
			else
				$nav .= '<a href="' . add_request($url, 'page=' . $i) . '">' . ($i + 1) . '</a> ';
		}
		
		if ($current != $max)
			$nav .= '<a href="' . add_request($url, 'page=' . ($current + 1)) . '">' . image('next.png', __('volgende'), __('Volgende pagina')) . '</a>';
		
		return $nav . "</div>\n";
	}

	public function thread_page_links(DataIterForumThread $thread, $pages)
	{
		$links = [];

		for ($page = 0; $page < $pages; ++$page)
			$links[] = sprintf('<a href="forum.php?thread=%d&amp;page=%d">%d</a>', $thread['id'], $page, $page + 1);

		return $links;
	}
}