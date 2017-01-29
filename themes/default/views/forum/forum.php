<?php
require_once 'include/models/DataModelPoll.php';

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

	public function render_forum(DataIterForum $forum)
	{
		$model = get_model('DataModelForum');

		$page = isset($_GET['page']) ? intval($_GET['page']) : 0;
		
		$threads = $model->get_threads($forum, $page, $max);

		return $this->twig->render('forum.twig', compact('forum', 'page', 'max', 'threads'));
	}

	public function render_thread(DataIterForumThread $thread)
	{
		$model = get_model('DataModelForum');

		$forum = $model->get_iter($thread['forum']);

		$page = isset($_GET['page']) ? intval($_GET['page']) : 0;

		$messages = $thread->get_messages($page, $max);

		return $this->twig->render('thread.twig', compact('thread', 'forum', 'page', 'messages', 'max'));
	}

	public function render_thread_form(DataIterForum $forum, DataIterForumThread $thread, DataIterForumMessage $message, array $errors)
	{
		$unified_authors = $this->get_unified_authors($forum, DataModelForum::ACL_WRITE);

		if ($message->has_id() && !array_key_exists($message['unified_author'], $unified_authors))
			$unified_authors[$iter['unified_author']] = __('(Onveranderd)');

		return $this->twig->render('thread_form.twig', compact('forum', 'thread', 'message', 'errors', 'unified_authors'));
	}

	public function render_thread_delete(DataIterForumThread $iter)
	{
		return $this->twig->render('thread_confirm_delete.twig', compact('iter'));
	}

	public function render_message_form(DataIterForumMessage $iter, array $errors)
	{
		$model = get_model('DataModelForum');

		$thread = $model->get_thread($iter['thread']);

		$forum = $model->get_iter($thread['forum']);

		$unified_authors = $this->get_unified_authors($forum, DataModelForum::ACL_REPLY);

		if ($iter->has_id() && !array_key_exists($iter['unified_author'], $unified_authors))
			$unified_authors[$iter['unified_author']] = __('(Onveranderd)');

		return $this->twig->render('reply_form.twig', compact('iter', 'thread', 'forum', 'errors', 'unified_authors'));
	}

	public function render_message_delete(DataIterForumMessage $iter)
	{
		return $this->twig->render('confirm_delete_message.twig', compact('iter'));
	}

	public function render_poll_form(DataIterForum $forum, DataIterPoll $poll, DataIterForumMessage $message, array $options, array $errors)
	{
		$unified_authors = $this->get_unified_authors($forum, DataModelForum::ACL_WRITE);

		if ($message->has_id() && !array_key_exists($message['unified_author'], $unified_authors))
			$unified_authors[$iter['unified_author']] = __('(Onveranderd)');

		return $this->twig->render('poll_form.twig', compact('forum', 'poll', 'message', 'options', 'errors', 'unified_authors'));
	}

	public function render_preview($text)
	{
		return markup_parse($text);
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

	public function get_unified_authors(DataIterForum $forum, $acl)
	{
		$model = get_model('DataModelForum');

		$authors = array();

		$member = get_identity()->member();
		$authors[DataModelForum::TYPE_PERSON . '_' . $member['id']] = $member['full_name'];

		$committee_model = get_model('DataModelCommissie');
		
		// TODO: Select using id instead if DataIterCommittee because $member['committees'] still returns id's instead of whole dataiters...
		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY))
			$committee_ids = array_select($committee_model->get(), 'id');
		else
			$committee_ids = $member['committees'];

		foreach ($committee_ids as $committee_id)
			if ($model->check_acl_commissie($forum, $acl, $committee_id))
				$authors[DataModelForum::TYPE_COMMITTEE . '_' . $committee_id] = $committee_model->get_naam($committee_id);

		return $authors;
	}

	public function get_author_link(DataIter $message, $last = false)
	{
		if ($last && $message['last_author_type'])
			$field = 'last_author';
		else
			$field = 'author';

		try {
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
		} catch (DataIterNotFoundException $e) {
			// Sometimes an author just doesnt exist anymore in the database. That's legacy for you!
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

	public function writeable_forums()
	{
		$model = get_model('DataModelForum');

		$writeable = array();

		$policy = get_policy('DataIterForumThread');

		// Writeable forums are forums in which a new thread can be created
		foreach ($model->get() as $forum)
			if ($policy->user_can_create($forum->new_thread()))
				$writeable[$forum['id']] = $forum['name'];

		return $writeable;
	}
}