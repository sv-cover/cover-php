<?php
require_once 'include/models/DataModelPoll.php';

class ForumView extends View
{
	// public function stylesheets()
	// {
	// 	return array_merge(parent::stylesheets(), [
	// 		get_theme_data('styles/forum.css')
	// 	]);
	// }

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

	public function render_forum_form(DataIterForum $forum)
	{
		return $this->twig->render('forum_form.twig', compact('forum'));
	}

	public function render_thread(DataIterForumThread $thread)
	{
		$forum = $thread['forum'];

		$page = isset($_GET['page']) ? intval($_GET['page']) : 0;

		$messages = $thread->get_messages($page, $max);

		return $this->twig->render('thread.twig', compact('thread', 'forum', 'page', 'messages', 'max'));
	}

	public function render_thread_form(DataIterForum $forum, DataIterForumThread $thread, DataIterForumMessage $message, array $errors)
	{
		$unified_authors = $this->get_unified_authors($forum, DataModelForum::ACL_WRITE);

		if ($message->has_id() && !array_key_exists($message['unified_author'], $unified_authors))
			$unified_authors[$message['unified_author']] = __('(Unchanged)');

		return $this->twig->render('thread_form.twig', compact('forum', 'thread', 'message', 'errors', 'unified_authors'));
	}

	public function render_thread_delete(DataIterForumThread $iter)
	{
		return $this->twig->render('thread_confirm_delete.twig', compact('iter'));
	}

	public function render_message_form(DataIterForumMessage $iter, array $errors)
	{
		$thread = $iter['thread'];

		$forum = $thread['forum'];

		$unified_authors = $this->get_unified_authors($forum, DataModelForum::ACL_REPLY);

		if ($iter->has_id() && !array_key_exists($iter['unified_author'], $unified_authors))
			$unified_authors[$iter['unified_author']] = __('(Unchanged)');

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
			$unified_authors[$message['unified_author']] = __('(Unchanged)');

		return $this->twig->render('poll_form.twig', compact('forum', 'poll', 'message', 'options', 'errors', 'unified_authors'));
	}

	public function render_preview($text)
	{
		return markup_parse($text);
	}

	public function get_unified_authors(DataIterForum $forum, $acl)
	{
		$model = get_model('DataModelForum');

		$authors = array();

		$member = get_identity()->member();

		if ($member === null) // Not logged in
			return [];

		$authors[DataModelForum::TYPE_PERSON . '_' . $member['id']] = $member['full_name'];

		$committee_model = get_model('DataModelCommissie');
		
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
					return $this->controller->generate_url('profile', ['lid' => $message[$field . '_id']]);
				
				case DataModelForum::TYPE_COMMITTEE: /* Commissie */
					$committee_model = get_model('DataModelCommissie');
					$committee = $committee_model->get_iter($message[$field . '_id']);
					return $this->controller->generate_url('committees', ['commissie' => urlencode($committee['login'])]);
				
				default:
					return null;
			}
		} catch (DataIterNotFoundException $e) {
			// Sometimes an author just doesnt exist anymore in the database. That's legacy for you!
			return null;
		}
	}

	public function get_author_photo(DataIter $message, $last = false)
	{
		if ($last && $message['last_author_type'])
			$field = 'last_author';
		else
			$field = 'author';

		try {
			switch (intval($message[$field . '_type']))
			{
				case DataModelForum::TYPE_PERSON: /* Person */
					return $this->controller->generate_url('profile_picture', ['format' => 'square', 'width' => 128, 'lid_id' => $message[$field . '_id']]);
				
				default:
					return null;
			}
		} catch (DataIterNotFoundException $e) {
			// Sometimes an author just doesnt exist anymore in the database. That's legacy for you!
			return null;
		}
	}

	public function page_navigation($url, $current, $max, $nav_num = 5)
	{
		$nav = '<nav class="pagination" role="navigation" aria-label="pagination">';

		if ($current != 0)
			$nav .= sprintf('<a href="%s" class="pagination-previous">%s</a>',
							markup_format_attribute(edit_url($url, ['page' => $current - 1])),
							markup_format_attribute(__('Previous')));
		
		if ($current != $max)
			$nav .= sprintf('<a href="%s" class="pagination-next">%s</a>',
				markup_format_attribute(edit_url($url, ['page' => $current + 1])),
				markup_format_attribute(__('Next page')));

		$nav .= '<ul class="pagination-list">';

		$nav_min = max(0, floor($current - ($nav_num / 2)));
		$nav_max = min($max, floor($current + ($nav_num / 2) - 1));
		
		if ($nav_min > 0)
			$nav .= sprintf('<li><a href="%1$s" class="pagination-link" aria-label="Goto page 1">1</a></li>', 
					markup_format_attribute(edit_url($url, ['page' => 0])));

		if ($nav_min > 1)
			$nav .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';

		if ($nav_max - $nav_min < $nav_num)
			$nav_max = min($max, $nav_min + $nav_num - 1);
		
		for ($i = $nav_min; $i <= $nav_max; $i++) {
			if ($i == $current) {
				$nav .= sprintf('<li><a class="pagination-link is-current" aria-label="Page %1$d" aria-current="page">%1$d</a></li>', $i + 1);
			}
			else {
				$nav .= sprintf('<li><a href="%1$s" class="pagination-link" aria-label="Goto page %2$d">%2$d</a></li>', 
					markup_format_attribute(edit_url($url, ['page' => $i])),
					$i + 1);
			}
		}

		if ($nav_max < $max - 1) 
			$nav .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';

		if ($nav_max < $max)
			$nav .= sprintf('<li><a href="%1$s" class="pagination-link" aria-label="Goto page %2$d">%2$d</a></li>', 
					markup_format_attribute(edit_url($url, ['page' => $max])),
					$max + 1);
				
		return $nav . "</ul></nav>\n";
	}

	public function thread_page_links(DataIterForumThread $thread, $pages)
	{
		$links = [];

		for ($page = 0; $page < $pages; ++$page)
			$links[] = sprintf('<a href="%s">%d</a>', $this->controller->generate_url('forum', ['thread'=> $thread['id'], 'page' => $page]), $page + 1);

		return $links;
	}

	public function has_unread_messages(DataIterForumThread $thread)
	{
		return get_auth()->logged_in() && $thread->has_unread_messages(get_identity()->member());
	}

	public function has_unread_threads(DataIter $forum)
	{
		return get_auth()->logged_in() && $forum->has_unread_threads(get_identity()->member());
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