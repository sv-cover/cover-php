<?php
require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/form.php';
require_once 'include/controllers/Controller.php';

class ControllerForum extends Controller
{
	public function __construct()
	{
		$this->model = get_model('DataModelForum');

		$this->view = View::byName('forum', $this);
	}

	private function _assert_access(DataIterForum $forum, $authorid, $acl)
	{
		$authorid = intval($authorid);
		
		if (!$this->model->check_acl($forum, $acl))
			throw new UnauthorizedException('You do not have the right permissions for this action.');

		if ($authorid != -1 && !$this->model->check_acl_commissie($forum, $acl, $authorid))
			throw new UnauthorizedException('You do not have the right permissions for this action.');

		if ($authorid > 0 && !get_identity()->member_in_committee($authorid))
			throw new UnauthorizedException('You do not have the right permissions for this action.');
	}

	private function _is_admin()
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}
	
	private function _assert_admin()
	{
		if (!$this->_is_admin())
			throw new UnauthorizedException('You need to be a member of the board or the AC/DCee for this action.');
	}
	
	public function _check_message_subject($name, $value)
	{
		if (!$value)
			return false;

		if (strlen($value) > 250)
			return false;
		
		return $value;
	}

	public function _check_unified_author($name, $value)
	{
		list($author_type, $author_id) = explode('_', $value, 2);

		switch ($author_type)
		{
			case DataModelForum::TYPE_PERSON:
				return $this->_is_admin() || get_identity()->member()->get('id') == $author_id ? $value : false;

			case DataModelForum::TYPE_COMMITTEE:
				return $this->_is_admin() || get_identity()->member_in_committee($author_id) ? $value : false;

			case DataModelForum::TYPE_GROUP:
				try {
					get_model('DataModelForum')->get_group($author_id);
				} catch (DataIterNotFoundException $e) {
					return false;
				}
				break;

			default:
				return false;
		}
	}
	
	private function _create_thread(DataIterForum $forum, $params)
	{
		$thread_data = check_values(array(
			array(
				'name' => 'subject',
				'function' => array($this, '_check_message_subject')
			),
			array(
				'name' => 'unified_author',
				'function' => array($this, '_check_unified_author')
			)
		), $errors);

		$message_data = check_values(array(
			'message',
			array(
				'name' => 'unified_author',
				'function' => array($this, '_check_unified_author')
			)
		), $errors);

		$thread = new DataIterForumThread($this->model, -1, $thread_data);
		
		$message = new DataIterForumMessage($this->model, -1, $mdata);
		
		if (count($errors) > 0)
			return $this->view->render_thread_form($forum, $thread, $message, $errors);
		
		// Create new thread with given subject
		$thread['forum'] = $forum['id'];
		$this->model->insert_thread($thread);
		
		// Create first message in the thread
		$message['thread'] = $thread['id'];
		$this->model->insert_message($message);

		// run_message_single redirects to the correct message in a thread
		return $this->run_message_single($message, $params);
	}

	public function run_forum_create(DataIterForum $forum, $params)
	{
		$this->_assert_access($forum, get_post('author'), DataModelForum::ACL_WRITE);
		
		if ($this->_forum_is_submitted('create_thread_' . $forum['id']))
			return $this->_create_thread($forum, $params);

		$empty_thread = new DataIterForumThread($this->model, null, []);

		$empty_message = new DataIterForumMessage($this->model, null, []);

		return $this->view->render_thread_form($forum, $empty_thread, $empty_message, array());
	}
	
	private function _process_forum_poll_vote(DataIterForumThread $thread)
	{
		if (!isset($_POST['optie']) || $_POST['optie'] === '')
			return $this->_view_thread($thread, null);
		
		$poll_model = get_model('DataModelPoll');
		
		if ($poll_model->voted($thread))
			return $this->_view_thread($thread, null);

		$poll_model->vote($_POST['optie']);
		
		return $this->view->redirect($_SERVER['HTTP_REFERER']);
	}
	
	public function _check_poll_subject($name, $value)
	{
		return strlen($value) > 150 ? false : $value;
	}		
			
	private function _process_forum_poll_nieuw(DataIterForum $forum)
	{
		$this->_assert_access($forum->get('id'), get_post('author'), DataModelForum::ACL_POLL);
		
		$tdata = check_values(array(
				array('name' => 'subject', 'function' => array(&$this, '_check_poll_subject'))), $terrors);
		$mdata = $this->_check_message_values($merrors);
		
		$errors = $terrors + $merrors;

		$opties = array();

		foreach ($_POST as $optie => $value) {
			if (strncmp($optie, 'optie_', 6) != 0)
				continue;
			
			if ($value == '')
				continue;
			
			if (strlen($value) > 150)
				$errors[] = $optie;
			else
				$opties[] = $value;
		}
		
		if (count($opties) == 0)
			$errors[] = 'optie_0';
		
		if (count($errors) > 0)
			return $this->view->render_add_poll($forum, $opties, $errors);
		
		// Create thread
		$tdata['poll'] = 1;
		$tdata['forum'] = intval($forum->get('id'));
		$this->_set_author_data($tdata);
		$iter = new DataIterForumThread($this->model, -1, $tdata);
		$tid = $this->model->insert_thread($iter);
		
		// Create message in thread
		$this->_set_author_data($mdata);
		$mdata['thread'] = intval($tid);	
		$iter = new DataIterForumMessage($this->model, -1, $mdata);
		$this->model->insert_message($iter);
		
		$poll_model = get_model('DataModelPoll');
		
		// Create poll options
		foreach ($opties as $optie) {
			$iter = new DataIter($this->model, -1,
				array(	'pollid' => $tid,
					'optie' => $optie));
			
			$poll_model->insert_optie($iter);
		}
		
		return $this->view->redirect('forum.php?thread=' . $tid);
	}
	
	private function _assert_may_edit_message(DataIterForumMessage $message)
	{
		if (!get_auth()->logged_in())
			throw new UnauthorizedException('You need to be logged in to edit a message.');
		
		if (!$message->editable())
			throw new UnauthorizedException('You do not have permission to edit this message.');
	}
	
	private function _view_admin($sub, DataIterForum $forum)
	{
		$this->_assert_admin();
		
		return $this->view->render_admin($forum, array('sub' => $sub));
	}
	
	private function _process_forum_order()
	{
		$this->_assert_admin();
		
		$order = explode(';', get_post('forum_order'));
		$headers = $this->model->get_headers();
		$delete = array();
		
		foreach ($headers as $header)
			$delete['-' . $header->get('id')] = $header;

		for ($i = 0; $i < count($order); $i++) {
			$info = explode('=', $order[$i], 2);

			if (substr($info[0], 0, 1) == '-') {
				/* Header */
				if ($info[0] == '-') {
					/* New header */
					$iter = new DataIterForumHeader($this->model, -1, array(
							'name' => $info[1],
							'position' => $i + 1));
					$this->model->insert_header($iter);
				} elseif (isset($delete[$info[0]])) {
					$header = $delete[$info[0]];
					$header->set('name', $info[1]);
					$header->set('position', $i + 1);

					$this->model->update_header($header);
					unset($delete[$info[0]]);
				}					
			} else {
				$iter = $this->model->get_iter($info[0]);
				$iter->set('position', $i + 1);
				$this->model->update($iter);
			}
		}
		
		foreach ($delete as $id => $header)
			$this->model->delete_header($header);
		
		return $this->view->redirect('forum.php?admin=forums');
	}
	
	private function _process_forum_forums()
	{
		$this->_assert_admin();
		
		$all_errors = array();
		
		foreach ($_POST as $key => $value) {
			if (strncmp($key, 'name_', 5) != 0)
				continue;
			
			$id = substr($key, 5);
			$forum = $this->model->get_iter($id);
			
			if (get_post('del_' . $id) == 'yes') {
				$this->model->delete($forum);
			} else {
				$data = check_values(array('name_' . $id, 'description_' . $id), $errors);
				
				if (count($errors) == 0) {
					$forum->set('name', $data['name_' . $id]);
					$forum->set('description', $data['description_' . $id]);
					$this->model->update($forum);
				} else {
					$all_errors += $errors;
				}
			}
		}
		
		if (count($all_errors) > 0) {
			return $this->view->render_admin(null, array('sub' => 'forums', 'errors' => $all_errors));
		} else {
			return $this->view->redirect('forum.php?admin=forums');
		}
	}
	
	private function _process_forum_nieuw()
	{
		$this->_assert_admin();
		
		$data = check_values(array('name', 'description'), $errors);
		
		if (count($errors) > 0)
			return $this->view->render_admin(null, array('sub' => 'forums', 'errors' => $errors));
					
		$iter = new DataIterForum($this->model, -1, $data);
		$this->model->insert($iter);
		
		return $this->view->redirect('forum.php?admin=forums');
	}
	
	private function _process_forum_rights(DataIterForum $forum) 
	{
		$this->_assert_admin();
		
		$acls = $this->model->get_acls();
		
		foreach ($_POST as $key => $value) {
			if (strncmp($key, 'right_', 6) != 0)
				continue;
			
			$id = substr($key, 6);
			$acl = $this->model->get_acl($id);
			
			if (!$acl)
				continue;
			
			if (get_post('del_' . $id) == 'yes') {
				$this->model->delete_acl($acl);
			} else {
				$i = 0;
				$perms = 0;
				
				foreach ($acls as $perm) {
					if (get_post('acl_' . $id . '_' . $i) == 'yes')
						$perms |= $perm;

					$i++;
				}
				
				$acl->set('permissions', $perms);
				$this->model->update_acl($acl);
			}
		}
		
		return $this->view->redirect('forum.php?admin=rights&forum=' . $forum->get('id'));
	}
	
	private function _process_forum_rights_nieuw(DataIterForum $forum)
	{
		$this->_assert_admin();
		
		$uid = null;
		
		switch (intval(get_post('type'))) {
			case DataModelForum::TYPE_ANONYMOUS: /* Everyone */
				$uid = -1;
			break;
			case DataModelForum::TYPE_PERSON: /* Member */
				$id = intval(get_post('member'));

				if ($id != 0) {
					$member_model = get_model('DataModelMember');
					$member = $member_model->get_iter($id);
				
					if ($member)
						$uid = $id;
				}
			break;
			case DataModelForum::TYPE_COMMITTEE: /* Commissie */
				$id = intval(get_post('commissie'));
				
				if ($id == -1)
					$uid = -1;
				elseif ($id != 0 || get_post('commissie') == '0') {
					$commissie_model = get_model('DataModelCommissie');
					$commissie = $commissie_model->get_iter($id);
					
					if ($commissie)
						$uid = $id;
				}
			break;
			case DataModelForum::TYPE_GROUP: /* Group */
				$id = intval(get_post('group'));
				
				if ($id == -1)
					$uid = -1;
				elseif ($id != 0) {
					$group = $this->model->get_group($id);
					
					if ($group)
						$uid = $id;
				}
			break;
			default:
				return $this->view->redirect('forum.php?admin=rights&forum=' . $forum->get('id'));
		}
		
		if ($uid === null)
			return $this->view->redirect('forum.php?admin=rights&forum=' . $forum->get('id'));
		
		$acls = $this->model->get_acls();
		$perm_names = array('read' => $acls[0], 'write' => $acls[1], 'reply' => $acls[2], 'poll' => $acls[3]);
		$perms = 0;
		
		foreach ($perm_names as $key => $value)
			$perms |= get_post($key) == 'yes' ? $value : 0;
		
		$iter = new DataIterForumPermission($this->model, -1, array(
				'forumid' => intval($forum->get('id')),
				'type' => intval(get_post('type')),
				'uid' => $uid,
				'permissions' => $perms));

		$this->model->insert_acl($iter);

		return $this->view->redirect('forum.php?admin=rights&forum=' . $forum->get('id'));
	}
	
	private function _process_forum_groups()
	{
		$this->_assert_admin();
		
		$all_errors = array();

		foreach ($_POST as $key => $value)
		{
			if (strncmp($key, 'group_', 6) != 0)
				continue;
			
			$id = substr($key, 6);
			$group = $this->model->get_group($id);
			
			if (!$group)
				continue;
			
			if (get_post('del_' . $id) == 'yes') {
				$this->model->delete_group($group);
			} else {
				$data = check_values(array(
						array('name' => 'name_' . $id, 'function' => array(&$this, '_check_group_name'))), $errors);
				
				if (count($errors) > 0) {
					$all_errors += $errors;
					continue;
				}

				$group->set('name', $data['name_' . $id]);
				$this->model->update_group($group);
			}
		}
		
		if (count($all_errors) > 0) {
			return $this->view->render_admin(null, array('sub' => 'groups', 'errors' => $all_errors));
		} else {
			return $this->view->redirect('forum.php?admin=groups');
		}
	}
	
	public function _check_group_name($name, $value)
	{
		if (!$value)
			return false;
		
		if (strlen($value) > 50)
			return false;
		
		return $value;
	}
	
	private function _process_forum_groups_nieuw()
	{
		$this->_assert_admin();
		
		$data = check_values(array(
				array('name' => 'name', 'function' => array(&$this, '_check_group_name'))), 
				$errors);
		
		if (count($errors) > 0)
			return $this->view->render_admin(null, array('sub' => 'groups', 'errors' => $errors));
		
		$iter = new DataIterForumGroup($this->model, -1, $data);
		$this->model->insert_group($iter);

		return $this->view->redirect('forum.php?admin=groups');
	}
	
	private function _process_forum_groups_members()
	{
		$this->_assert_admin();
		
		/* Check group */
		$group = $this->model->get_group(get_post('guid'));
		
		if (!$group)
			return $this->view->render_admin(null, array('sub' => 'groups', 'errors' => array('guid')));
		
		switch (intval(get_post('type'))) {
			case DataModelForum::TYPE_ANONYMOUS: /* Everyone */
				$uid = -1;
			break;
			case DataModelForum::TYPE_PERSON: /* Member */
				$id = intval(get_post('member'));

				if ($id != 0) {
					$member_model = get_model('DataModelMember');
					$member = $member_model->get_iter($id);
					$uid = $member->get('id');
				}
			break;
			case DataModelForum::TYPE_COMMITTEE: /* Commissie */
				$id = intval(get_post('commissie'));
				
				if ($id == -1)
					$uid = -1;
				elseif ($id != 0 || get_post('commissie') == '0') {
					$commissie_model = get_model('DataModelCommissie');
					$commissie = $commissie_model->get_iter($id);
					$uid = $commissie->get('id');
				}
			break;
			default:
				return $this->view->redirect('forum.php?admin=groups');
			break;
		}
		
		$iter = new DataIterForumGroupMember($this->model, -1, array(
				'guid' => intval($group->get('id')),
				'type' => intval(get_post('type')),
				'uid' => $uid));

		$this->model->insert_group_member($iter);
		return $this->view->redirect('forum.php?admin=groups');
	}
	
	private function _process_forum_special()
	{
		$this->_assert_admin();
		
		$specials = array('poll', 'news', 'weblog');
		$config_model = get_model('DataModelConfiguratie');

		foreach ($specials as $special) {
			if (!isset($_POST[$special]))
				continue;
			
			$iter = $config_model->get_iter($special . '_forum');
			
			$forum = $this->model->get_iter(get_post($special));
			
			$iter->set('value', intval($forum->get('id')));

			$config_model->update($iter);
		}
		
		return $this->view->redirect('forum.php?admin=special');
	}
	
	private function _process_forum_groups_del_member($id)
	{
		$this->_assert_admin();

		$member = $this->model->get_group_member($id);
		
		$this->model->delete_group_member($member);
	
		return $this->view->redirect('forum.php?admin=groups');
	}

	private function _create_message(DataIterForumMessage $message, array $data, array &$errors)
	{
		$message_data = check_values([
			'message',
			['name' => 'unified_author', 'function' => [$this, '_check_unified_author']]
		], $errors, $data);

		if (count($errors) > 0)
			return false;

		$message->set_all($message_data);
		$message->set_literal('date', 'CURRENT_TIMESTAMP');

		$this->model->insert_message($message);

		return true;
	}
	
	private function _update_message(DataIterForumMessage $message, array $data, array &$errors)
	{
		$message_data = check_values(['message'], $errors, $data);

		// If the author is changed, check it
		if (isset($data['unified_author']) && $data['unified_author'] != $message['unified_author'])
			$message_data = array_merge($message_data,
				check_values([['name' => 'unified_author', 'function' => [$this, '_check_unified_author']]], $errors, $data));
		
		if (count($errors) > 0)
			return false;

		$message->set_all($message_data);

		$this->model->update_message($message);

		return true;
	}

	private function _process_forum_del_message($id, $params)
	{
		$this->_assert_admin();

		$iter = $this->model->get_message($id);
		
		// If this is the first message, delete the whole thread
		if ($iter->is_first_message()) {
			$thread = $this->model->get_thread($iter->thread);
			$this->model->delete_thread($thread);
			return $this->view->redirect('forum.php?forum=' . $thread->get('forum'));
		} else {
			$ret = $this->model->delete_message($iter);
			
			if (is_int($ret)) {
				return $this->view->redirect('forum.php?forum=' . $ret);
			} else {
				return $this->view->redirect('forum.php?thread=' . $iter->get('thread') . (isset($params['page']) ? ('&page=' . $params['page']) : ''));
			}
		}
	}

	public function run_forum_index(DataIterForum $forum)
	{
		$this->model->set_forum_session_read($forum->get('id'));
		return $this->view->render_forum($forum);
	}
	
	public function run_thread_index(DataIterForumThread $thread)
	{
		/* Mark the thread as read */
		$this->model->set_forum_session_read($thread->get('forum'));
		$this->model->mark_read($thread);
		return $this->view->render_thread($thread);
	}

	public function run_thread_create(DataIterForum $forum)
	{
		$thread = $forum->new_thread();

		$errors = array();

		if ($this->_form_is_submitted('create_thread', $forum))
			if ($this->_create_thread($thread, $_POST, $errors))
				return $this->view->redirect('forum.php?thread=' . $thread['id']);

		return $this->view->render_thread_form($thread, $errors);
	}

	public function run_thread_delete(DataIterForumThread $thread)
	{

	}

	public function run_thread_reply(DataIterForumThread $thread)
	{
		$message = $thread->new_message();

		if (isset($_GET['quote_message'])) {
			try {
				$quoted_message = $this->model->get_message($_GET['quote_message']);
				$quoted_author = $this->model->get_author_info($quoted_message);
				$message['message'] = sprintf("[quote=%s]%s[/quote]\n\n", $quoted_author['name'], $quoted_message['message']);
			} catch (DataIterNotFoundException $e) {
				// Yeah, it's not really an issue if we can't find the message we wanted to quote.
				// get_author_info wont fail anyway because that does'nt throw when the author cannot be found, it will just return __('Onbekend').
			}
		}

		$errors = [];

		if ($this->_form_is_submitted('reply', $thread))
			if ($this->_create_message($message, $_POST, $errors))
				return $this->run_message_single($message);

		return $this->view->render_message_form($message, $errors);
	}
	
	public function run_message_update(DataIterForumMessage $message)
	{
		if (!get_policy($message)->user_can_update($message))
			throw new UnauthorizedException('You are not allowed to modify this message.');

		$errors = [];

		if ($this->_form_is_submitted('update_message', $message))
			if ($this->_update_message($message, $_POST, $errors))
				return $this->run_message_single($message);

		return $this->view->render_message_form($message, $errors);
	}

	public function run_message_delete(DataIterForumMessage $message)
	{
		if (!get_policy($message)->user_can_delete($message))
			throw new UnauthorizedException('You are not allowed to delete this message.');

		$thread = $this->model->get_thread($message['thread']);

		if ($message->is_first_message())
			return $this->run_thread_delete($thread);

		if ($this->_form_is_submitted('delete_message', $message))
			if ($this->_delete_message($message))
				return $this->view->redirect(sprintf('forum.php?thread=%d&page=%d', $thread['id'], $thread['num_thread_pages']));

		return $this->view->render_message_delete($message);
	}

	public function run_message_single(DataIterForumMessage $message)
	{
		return $this->view->redirect(sprintf('forum.php?thread=%d&page=%d#p%d', $message['thread'], $message['thread_page'], $message['id']));
	}
	
	public function run_index()
	{
		/* Set last visit for all fora to current time */
		$this->model->update_last_visit();

		$forums = $this->model->get();

		return $this->view->render_index($forums);
	}
	
	public function run_preview()
	{
		return $this->view->render_preview(get_post('message'));
	}
	
	private function _process_forum_move_thread($thread_id, $forum_id)
	{
		if (!get_auth()->logged_in())
			throw new UnauthorizedException('Log in to move threads');

		$thread = $this->model->get_thread($thread_id);
		
		$forum = $this->model->get_iter($forum_id);
		
		if (!$thread->editable())
			throw new UnauthorizedException('You do not have permission to move threads');
		
		$thread->forum = $forum->get('id');
		$this->model->update_thread($thread);
		
		return $this->view->redirect('forum.php?thread=' . $thread->get('id'));
	}

	protected function run_impl()
	{
		$forum = null;
		$thread = null;
		$params = array();

		$view = isset($_GET['view']) ? $_GET['view'] : null;

		$admin = get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			  || get_identity()->member_in_committee(COMMISSIE_EASY);
		
		if (isset($_GET['message'])) {
			$message = $this->model->get_message($_GET['message']);
		
			if ($view == 'update')
				return $this->run_message_update($message);
			else
				return $this->run_message_single($message);
		}
		elseif (isset($_GET['thread'])) {
			$thread = $this->model->get_thread($_GET['thread']);

			if ($view == 'reply')
				return $this->run_thread_reply($thread);
			else
				return $this->run_thread_index($thread);
		}
		elseif (isset($_GET['forum'])) {
			$forum = $this->model->get_iter($_GET['forum'], $admin ? -1 : DataModelForum::ACL_READ);

			if ($view == 'create')
				return $this->run_thread_create($forum);
			elseif ($view == 'create_poll')
				return $this->run_poll_create($forum);
			else
				return $this->run_forum_index($forum);
		}
		elseif ($view == 'preview')
			return $this->run_preview();
		else
			return $this->run_index();
	}
}

$controller = new ControllerForum();
$controller->run();
