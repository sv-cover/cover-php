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
	
	private function _assert_access($forumid, $authorid, $acl)
	{
		$authorid = intval($authorid);
		
		if (!$this->model->check_acl($forumid, $acl))
			throw new UnauthorizedException('You do not have the right permissions for this action.');

		if ($authorid != -1 && !$this->model->check_acl_commissie($forumid, $acl, $authorid))
			throw new UnauthorizedException('You do not have the right permissions for this action.');

		if ($authorid > 0 && !get_identity()->member_in_committee($authorid))
			throw new UnauthorizedException('You do not have the right permissions for this action.');
	}
	
	public function _check_message_subject($name, $value)
	{
		if (!$value)
			return false;

		if (strlen($value) > 250)
			return false;
		
		return $value;
	}
	
	public function _check_message_values(&$errors)
	{
		return check_values(array('message'), $errors);
	}
	
	private function _set_author_data(&$data)
	{
		$author = intval(get_post('author'));

		if ($author >= 0) {
			/* Commissie */
			$data['author'] = $author;
			$data['author_type'] = 2;
		} else {
			/* Member */
			$member_data = logged_in();
			$data['author'] = intval($member_data['id']);
			$data['author_type'] = 1;
		}
	}
	
	private function _process_new_thread($params)
	{
		$forum = $this->model->get_iter(get_post('parent_id'));
		
		$this->_assert_access($forum->get('id'), get_post('author'), DataModelForum::ACL_WRITE);
		
		$mdata = $this->_check_message_values($merrors);
		$tdata = check_values(array(
				array('name' => 'subject', 'function' => array(&$this, '_check_message_subject'))), $terrors);
		
		$errors = $merrors + $terrors;
		
		if (count($errors) > 0) {
			$params['errors'] = $errors;
			$this->get_content('forum', $forum, $params);
			return;
		}
		
		// Create new thread with given subject
		$tdata['forum'] = intval($forum->get('id'));
		$this->_set_author_data($tdata);
		$iter = new DataIterForumThread($this->model, -1, $tdata);
		$tid = $this->model->insert_thread($iter);
		
		// Create first message in the thread
		$this->_set_author_data($mdata);
		$mdata['thread'] = intval($tid);	
		$iter = new DataIterForumMessage($this->model, -1, $mdata);
		$this->model->insert_message($iter);

		return $this->view->redirect('forum.php?thread=' . $tid);
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
	
	private function _process_new_message($params)
	{
		$thread = $this->model->get_thread(get_post('parent_id'));
		
		$this->_assert_access($thread->get('forum'), get_post('author'), DataModelForum::ACL_REPLY);
		
		$data = $this->_check_message_values($errors);
		
		if (count($errors) > 0) {
			$params['errors'] = $errors;
			return $this->view->render_thread($thread, $params);
		}
		
		$data['thread'] = intval($thread->get('id'));
		$this->_set_author_data($data);

		$iter = new DataIterForumMessage($this->model, -1, $data);
		$this->model->insert_message($iter);
		$page = $thread->get_num_thread_pages() - 1;

		return $this->view->redirect('forum.php?thread=' . $thread->get('id') . '&page=' . $page);
	}
	
	private function _assert_admin()
	{
		if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			&& !get_identity()->member_in_committee(COMMISSIE_EASY))
			throw new UnauthorizedException('You need to be a member of the board or the AC/DCee for this action.');
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
	
	private function _process_forum_mod_message($id, $params)
	{
		$message = $this->model->get_message($id);

		$this->_assert_may_edit_message($message);
		
		$data = $this->_check_message_values($merrors);
		$terrors = array();
		$tdata = array();
		$first = $message->is_first_message();
		
		if ($first) {
			$tdata = check_values(array(
				array('name' => 'subject', 'function' => array(&$this, '_check_message_subject'))), $terrors);
		}
				
		$errors = $merrors + $terrors;

		if (count($errors) > 0) {
			$params['errors'] = $errors;
			return $this->view->render_mod_message($message, $params);
		}

		$message->message = $data['message'];
		$this->model->update_message($message);

		if ($first) {
			$thread = $this->model->get_thread($message->thread);
			$thread->subject = $tdata['subject'];
			$this->model->update_thread($thread);
		}

		return $this->view->redirect('forum.php?thread=' . $message->thread . '&page=' . (isset($params['page']) ? '&page=' . $params['page'] : '') . '#p' . $message->id);
	}
	
	private function _view_forum_del_message($id, $params) 
	{
		$this->_assert_admin();
		
		$message = $this->model->get_message($id);
		
		return $this->view->render_del_message($message, $params);
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
	
	private function _view_forum(DataIterForum $forum, $params)
	{
		$this->model->set_forum_session_read($forum->get('id'));
		return $this->view->render_forum($forum, $params);
	}
	
	private function _view_thread(DataIterForumThread $thread, $params)
	{
		/* Mark the thread as read */
		$this->model->set_forum_session_read($thread->get('forum'));
		$this->model->mark_read($thread);
		return $this->view->render_thread($thread, $params);
	}
	
	private function _view_mod_message($messageid, $params)
	{
		$message = $this->model->get_message($messageid);
		
		if (!$message->editable())
			throw new UnauthorizedException('You are not allowed to modify this message');

		return $this->view->render_mod_message($message, $params);
	}
	
	private function _view_index()
	{
		/* Set last visit for all fora to current time */
		$this->model->update_last_visit();
		
		return $this->view->render_index($this->model->get());
	}
	
	private function _view_preview()
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

		$admin = get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			  || get_identity()->member_in_committee(COMMISSIE_EASY);
		
		if (isset($_GET['forum']))
			$forum = $this->model->get_iter($_GET['forum'], $admin ? -1 : DataModelForum::ACL_READ);
		elseif (isset($_GET['thread']))
			$thread = $this->model->get_thread($_GET['thread']);

		if (isset($_GET['page']))
			$params['page'] = intval($_GET['page']);
		elseif (isset($_POST['page']))
			$params['page'] = intval($_POST['page']);
		
		if (isset($_GET['startadd']))
			$params['startadd'] = true;
		
		if (isset($_GET['preview']))
			return $this->_view_preview();
		elseif (isset($_POST['submforumnewthread']))
			return $this->_process_new_thread($params);
		elseif (isset($_POST['submforumnewmessage']))
			return $this->_process_new_message($params);
		elseif (isset($_POST['submforumorder']))
			return $this->_process_forum_order();
		elseif (isset($_POST['submforumforums']))
			return $this->_process_forum_forums();
		elseif (isset($_POST['submforumnieuw']))
			return $this->_process_forum_nieuw();
		elseif (isset($_POST['submforumrights']))
			return $this->_process_forum_rights($forum);
		elseif (isset($_POST['submforumrightsnieuw']))
			return $this->_process_forum_rights_nieuw($forum);
		elseif (isset($_POST['submforumgroups']))
			return $this->_process_forum_groups();
		elseif (isset($_POST['submforumgroupsnieuw']))
			return $this->_process_forum_groups_nieuw();
		elseif (isset($_POST['submforumgroupsmembers']))
			return $this->_process_forum_groups_members();
		elseif (isset($_POST['submforumspecial']))
			return $this->_process_forum_special();
		elseif (isset($_POST['submforummodmessage']))
			return $this->_process_forum_mod_message($_POST['message_id'], $params);
		elseif (isset($_POST['submforumdelmessage']))
			return $this->_process_forum_del_message($_POST['message_id'], $params);
		elseif (isset($_POST['submforummovethread']))
			return $this->_process_forum_move_thread($_POST['thread_id'], $_POST['forum_id'], $params);
		elseif (isset($_GET['delmessage']))
			return $this->_view_forum_del_message($_GET['delmessage'], $params);
		elseif (isset($_GET['admin']) && isset($_GET['delmember']))
			return $this->_process_forum_groups_del_member($_GET['delmember']);
		elseif (isset($_GET['modmessage']))
			return $this->_view_mod_message($_GET['modmessage'], $params);
		elseif (isset($_GET['admin']))
			return $this->_view_admin($_GET['admin'], $forum);
		elseif (isset($_POST['submforumpollnieuw']))
			return $this->_process_forum_poll_nieuw($forum);
		elseif (isset($_POST['submforumpollvote']) && $thread)
			return $this->_process_forum_poll_vote($thread);
		elseif (isset($_GET['addpoll']) && $forum)
			$this->get_content('add_poll', $forum, $params);
		elseif ($forum)
			return $this->_view_forum($forum, $params);
		elseif ($thread)
			return $this->_view_thread($thread, $params);
		else
			return $this->_view_index();
	}
}

$controller = new ControllerForum();
$controller->run();
