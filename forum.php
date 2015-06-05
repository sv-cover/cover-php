<?php
	require_once 'include/init.php';
	require_once 'include/member.php';
	require_once 'include/form.php';
	require_once 'include/controllers/Controller.php';
	
	class ControllerForum extends Controller
	{
		var $model = null;

		function ControllerForum() {
			$this->model = get_model('DataModelForum');
		}
		
		function get_content($view, $iter = null, $params = null) {
			$this->run_header(array('title' => __('Forum')));
			run_view('forum::' . $view, $this->model, $iter, $params);
			$this->run_footer();
		}
		
		function _page_prepare($forumid, $authorid, $acl) {
			$authorid = intval($authorid);
			
			if (!$this->model->check_acl($forumid, $acl)) {
				$this->get_content('auth');
				return false;
			} elseif ($authorid != -1 && !$this->model->check_acl_commissie($forumid, $acl, $authorid)) {
				$this->get_content('auth');
				return false;
			}

			if ($authorid > 0 && !member_in_commissie($authorid)) {
				$this->get_content('auth');
				return false;
			}
			
			return true;
		}
		
		function _check_message_subject($name, $value) {
			if (!$value)
				return false;

			if (strlen($value) > 250)
				return false;
			
			return $value;
		}
		
		function _check_message_values(&$errors) {
			return check_values(array('message'), $errors);
		}
		
		function _set_author_data(&$data) {
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
		
		function _process_new_thread($params) {
			$forum = $this->model->get_iter(get_post('parent_id'));
			
			if (!$forum) {
				$this->get_content('forum_not_found');
				return;
			}
			
			if (!$this->_page_prepare($forum->get('id'), get_post('author'), ACL_WRITE))
				return;	
			
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
			$iter = new DataIter($this->model, -1, $tdata);
			$tid = $this->model->insert_thread($iter);
			
			// Create first message in the thread
			$this->_set_author_data($mdata);
			$mdata['thread'] = intval($tid);	
			$iter = new DataIter($this->model, -1, $mdata);
			$this->model->insert_message($iter);

			header('Location: forum.php?thread=' . $tid);
			exit();
		}
		
		function _process_forum_poll_vote($thread) {
			if (!isset($_POST['optie']) || $_POST['optie'] === '') {
				$this->_view_thread($thread, null);
				return;
			}
			
			$poll_model = get_model('DataModelPoll');
			
			if ($poll_model->voted($thread)) {
				$this->_view_thread($thread, null);
				return;
			}

			$poll_model->vote($_POST['optie']);
			
			header('Location: ' . $_SERVER['HTTP_REFERER']);
		}
		
		function _check_poll_subject($name, $value) {
			if (strlen($value) > 150)
				return false;
			
			return $value;		
		}		
				
		function _process_forum_poll_nieuw($forum) {
			if (!$forum) {
				$this->get_content('forum_not_found');
				return;
			}
			
			if (!$this->_page_prepare($forum->get('id'), get_post('author'), ACL_POLL))
				return;
			
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
			
			if (count($errors) > 0) {
				$this->get_content('add_poll', $forum, array('errors' => $errors, 'options' => $opties));
				return;
			}
			
			// Create thread
			$tdata['poll'] = 1;
			$tdata['forum'] = intval($forum->get('id'));
			$this->_set_author_data($tdata);
			$iter = new DataIter($this->model, -1, $tdata);
			$tid = $this->model->insert_thread($iter);
			
			// Create message in thread
			$this->_set_author_data($mdata);
			$mdata['thread'] = intval($tid);	
			$iter = new DataIter($this->model, -1, $mdata);
			$this->model->insert_message($iter);
			
			$poll_model = get_model('DataModelPoll');
			
			// Create poll options
			foreach ($opties as $optie) {
				$iter = new DataIter($this->model, -1,
					array(	'pollid' => $tid,
						'optie' => $optie));
				
				$poll_model->insert_optie($iter);
			}
			
			header('Location: forum.php?thread=' . $tid);
		}
		
		function _process_new_message($params) {
			$thread = $this->model->get_thread(get_post('parent_id'));
			
			if (!$thread) {
				$this->get_content('thread_not_found');
				return;
			}

			if (!$this->_page_prepare($thread->get('forum'), get_post('author'), ACL_REPLY))
				return;
			
			$data = $this->_check_message_values($errors);
			
			if (count($errors) > 0) {
				$params['errors'] = $errors;
				$this->get_content('thread', $thread, $params);
				return;		
			}
			
			$data['thread'] = intval($thread->get('id'));
			$this->_set_author_data($data);

			$iter = new DataIter($this->model, -1, $data);
			$this->model->insert_message($iter);
			$page = $thread->get_num_thread_pages() - 1;

			header('Location: forum.php?thread=' . $thread->get('id') . '&page=' . $page);
			exit();
		}
		
		function _admin_prepare() {
			if (!member_in_commissie(COMMISSIE_BESTUUR)) {
				$this->get_content('auth');
				return false;
			}
			
			return true;
		}
		
		function _edit_prepare($message) {
			$info = logged_in();
			
			if (!$info) {
				$this->get_content('auth');
				return false;
			}
			
			if (!$message->editable()) {
				$this->get_content('not_editable');
				return false;
			}
	
			return true;
		}
		
		function _view_admin($sub, $forum) {
			if (!$this->_admin_prepare())
				return;
			
			$this->get_content('admin', $forum, array('sub' => $sub));
		}
		
		function _process_forum_order() {
			if (!$this->_admin_prepare())
				return;
			
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
						$iter = new DataIter($this->model, -1, array(
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
					
					if (!$iter)
						continue;
					
					$iter->set('position', $i + 1);
					$this->model->update($iter);
				}
			}
			
			foreach ($delete as $id => $header)
				$this->model->delete_header($header);
			
			header('Location: forum.php?admin=forums');
			exit();
		}
		
		function _process_forum_forums() {
			if (!$this->_admin_prepare())
				return;
			
			$all_errors = array();
			
			foreach ($_POST as $key => $value) {
				if (strncmp($key, 'name_', 5) != 0)
					continue;
				
				$id = substr($key, 5);
				$forum = $this->model->get_iter($id);
				
				if (!$forum)
					continue;
				
				if (get_post('del_' . $id) == 'yes') {
					$this->model->delete($forum);
				} else {
					$data = check_values(array(
							'name_' . $id,
							'description_' . $id),
							$errors);
					
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
				$this->get_content('admin', null, array('sub' => 'forums', 'errors' => $all_errors));
			} else {
				header('Location: forum.php?admin=forums');
				exit();
			}
		}
		
		function _process_forum_nieuw() {
			if (!$this->_admin_prepare())
				return;
			
			$data = check_values(array(
					'name',
					'description'), $errors);
			
			if (count($errors) > 0) {
				$this->get_content('admin', null, array('sub' => 'forums', 'errors' => $errors));
				return;
			}
			
			$iter = new DataIter($this->model, -1, $data);
			$this->model->insert($iter);
			
			header('Location: forum.php?admin=forums');
			exit();
		}
		
		function _process_forum_rights($forum) {
			if (!$this->_admin_prepare())
				return;
			
			if (!$forum) {
				$this->get_content('forum_not_found');
				return;
			}
			
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
			
			header('Location: forum.php?admin=rights&forum=' . $forum->get('id'));
			exit();
		}
		
		function _process_forum_rights_nieuw($forum) {
			if (!$this->_admin_prepare())
				return;
			
			if (!$forum) {
				$this->get_content('forum_not_found');
				return;
			}
			
			$uid = null;
			
			switch (get_post('type')) {
				case '-1': /* Everyone */
					$uid = -1;
				break;
				case '1': /* Member */
					$id = intval(get_post('member'));
	
					if ($id != 0) {
						$member_model = get_model('DataModelMember');
						$member = $member_model->get_iter($id);
					
						if ($member)
							$uid = $id;
					}
				break;
				case '2': /* Commissie */
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
				case '3': /* Group */
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
					header('Location: forum.php?admin=rights&forum=' . $forum->get('id'));
					exit();
				break;
			}
			
			if ($uid === null) {
				header('Location: forum.php?admin=rights&forum=' . $forum->get('id'));
				exit();
			}
			
			$acls = $this->model->get_acls();
			$perm_names = array('read' => $acls[0], 'write' => $acls[1], 'reply' => $acls[2], 'poll' => $acls[3]);
			$perms = 0;
			
			foreach ($perm_names as $key => $value)
				$perms |= get_post($key) == 'yes' ? $value : 0;
			
			$iter = new DataIter($this->model, -1, array(
					'forumid' => intval($forum->get('id')),
					'type' => intval(get_post('type')),
					'uid' => $uid,
					'permissions' => $perms));

			$this->model->insert_acl($iter);
			header('Location: forum.php?admin=rights&forum=' . $forum->get('id'));
			exit();
		}
		
		function _process_forum_groups() {
			if (!$this->_admin_prepare())
				return;
			
			$all_errors = array();

			foreach ($_POST as $key => $value) {
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
				$this->get_content('admin', null, array('sub' => 'groups', 'errors' => $all_errors));
			} else {
				header('Location: forum.php?admin=groups');
				exit();
			}
		}
		
		function _check_group_name($name, $value) {
			if (!$value)
				return false;
			
			if (strlen($value) > 50)
				return false;
			
			return $value;
		}
		
		function _process_forum_groups_nieuw() {
			if (!$this->_admin_prepare())
				return;
			
			$data = check_values(array(
					array('name' => 'name', 'function' => array(&$this, '_check_group_name'))), 
					$errors);
			
			if (count($errors) > 0) {
				$this->get_content('admin', null, array('sub' => 'groups', 'errors' => $errors));
				return;
			}
			
			$iter = new DataIter($this->model, -1, $data);
			$this->model->insert_group($iter);

			header('Location: forum.php?admin=groups');
			exit();
		}
		
		function _process_forum_groups_members() {
			if (!$this->_admin_prepare())
				return;
			
			/* Check group */
			$group = $this->model->get_group(get_post('guid'));
			
			if (!$group) {
				$this->get_content('admin', null, array('sub' => 'groups', 'errors' => array('guid')));
				return;
			}
			
			switch (get_post('type')) {
				case '-1': /* Everyone */
					$uid = -1;
				break;
				case '1': /* Member */
					$id = intval(get_post('member'));
	
					if ($id != 0) {
						$member_model = get_model('DataModelMember');
						$member = $member_model->get_iter($id);
					
						if ($member)
							$uid = $id;
					}
				break;
				case '2': /* Commissie */
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
				default:
					header('Location: forum.php?admin=groups');
					exit();
				break;
			}
			
			$iter = new DataIter($this->model, -1, array(
					'guid' => intval($group->get('id')),
					'type' => intval(get_post('type')),
					'uid' => $uid));

			$this->model->insert_group_member($iter);
			header('Location: forum.php?admin=groups');
		}
		
		function _process_forum_special() {
			if (!$this->_admin_prepare())
				return;
			
			$specials = array('poll', 'news', 'weblog');
			$config_model = get_model('DataModelConfiguratie');

			foreach ($specials as $special) {
				if (!isset($_POST[$special]))
					continue;
				
				$iter = $config_model->get_iter($special . '_forum');
				
				if ($iter === null)
					continue;
				
				$forum = $this->model->get_iter(get_post($special));
				
				if (!$forum)
					continue;
				
				$iter->set('value', intval($forum->get('id')));
				$config_model->update($iter);
			}
			
			header('Location: forum.php?admin=special');
		}
		
		function _process_forum_groups_del_member($id) {
			if (!$this->_admin_prepare())
				return;

			$member = $this->model->get_group_member($id);
			
			if ($member)
				$this->model->delete_group_member($member);
		
			header('Location: forum.php?admin=groups');
		}
		
		function _process_forum_mod_message($id, $params) {
			$message = $this->model->get_message($id);

			if (!$this->_edit_prepare($message))
				return;
			
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
				$this->get_content('mod_message', $message, $params);
				return;		
			}

			$message->message = $data['message'];
			$this->model->update_message($message);

			if ($first) {
				$thread = $this->model->get_thread($message->thread);
				$thread->subject = $tdata['subject'];
				
				$this->model->update_thread($thread);
			}

			header('Location: forum.php?thread=' . $message->thread . '&page=' . (isset($params['page']) ? '&page=' . $params['page'] : '') . '#p' . $message->id);
			exit();			
		}
		
		function _view_forum_del_message($id, $params) {
			if (!$this->_admin_prepare())
				return;
			
			$message = $this->model->get_message($id);
			
			if (!$message)
				$this->get_content('message_not_found');
			else
				$this->get_content('del_message', $message, $params);	
		}
		
		function _process_forum_del_message($id, $params) {
			if (!$this->_admin_prepare())
				return;

			$iter = $this->model->get_message($id);
			$first = $iter->is_first_message();

			if (!$iter) {
				$this->get_content('message_not_found');
				return;
			}
			
			if ($first) {
				$thread = $this->model->get_thread($iter->thread);
				$news_forum = $this->model->get_special_forum('news');

				if ($iter->get('forum') == $news_forum->get_id()) {
					//Message is a news post. We have to notify the owner of the deletion
					$subject = __('Mededeling verwijderd');
					$body =  sprintf(__("De mededeling met het onderwerp '%s' is verwijderd door het bestuur. Mocht je vragen hebben hierover, kun je het bestuur mailen op bestuur@svcover.nl.\n\nMet vriendelijke groeten,\n\nDe WebCie"), $iter->get('subject'));

					$author = $this->model->get_author_info($iter);
					mail($author['email'], $subject, $body, "From: webcie@ai.rug.nl\r\n");
				}

				$this->model->delete_thread($thread);
				header('Location: forum.php?forum=' . $thread->get('forum'));
			} else {
				$ret = $this->model->delete_message($iter);
				
				if (is_int($ret)) {
					header('Location: forum.php?forum=' . $ret);
				} else {
					header('Location: forum.php?thread=' . $iter->get('thread') . (isset($params['page']) ? ('&page=' . $params['page']) : ''));
				}
			}
		}
		
		function _view_forum($forum, $params) {
			$this->model->set_forum_session_read($forum->get('id'));
			
			$this->get_content('forum', $forum, $params);
		}
		
		function _view_thread($thread, $params) {
			/* Mark the thread as read */
			$this->model->set_forum_session_read($thread->get('forum'));
			$this->model->mark_read($thread->get('id'));

			$this->get_content('thread', $thread, $params);
		}
		
		function _view_mod_message($messageid, $params) {
			$message = $this->model->get_message($messageid);
			
			if (!$message)
				$this->get_content('message_not_found');
			elseif (!$message->editable())
				$this->get_content('not_editable');
			else
				$this->get_content('mod_message', $message, $params);
		}
		
		function _view_fora() {
			/* Set last visit for all fora to current time */
			$this->model->update_last_visit();
			
			$this->get_content('fora', $this->model->get());
		}
		
		function _view_preview() {
			ob_end_clean();

			if (!$data)
				$data = get_post('message');

			run_view('forum::preview', $this->model, null, array('message' => $data));
			exit();
		}
		
		function _process_forum_move_thread($threadid, $forumid) {
			if (!logged_in()) {
				$this->run_content('auth');
				return;
			}
			
			$thread = $this->model->get_thread($threadid);
			
			if (!$thread) {
				$this->run_content('thread_not_found');
				return;
			}
			
			$forum = $this->model->get_iter($forumid);
			
			if (!$forum) {
				$this->run_content('forum_not_found');
				return;
			}
			
			if (!$thread->editable()) {
				$this->run_content('auth');
				return;
			}
			
			$thread->forum = $forum->id;
			$this->model->update_thread($thread);
			
			header('Location: forum.php?thread=' . $thread->id);
			exit();
		}

		function run_impl() {
			$forum = null;
			$thread = null;
			$params = array();
			
			if (isset($_GET['forum'])) {
				$forum = $this->model->get_iter($_GET['forum'], isset($_GET['admin']) ? -1 : ACL_READ);
				
				if (!$forum) {
					$this->get_content('forum_not_found');
					return;	
				}
			} else if (isset($_GET['thread'])) {
				$thread = $this->model->get_thread($_GET['thread']);
				
				if (!$thread) {
					$this->get_content('thread_not_found');
					return;
				}
			}
			
			if (isset($_GET['page']))
				$params['page'] = intval($_GET['page']);
			elseif (isset($_POST['page']))
				$params['page'] = intval($_POST['page']);
			
			if (isset($_GET['startadd']))
				$params['startadd'] = true;
			
			if (isset($_GET['preview']))
				$this->_view_preview();
			elseif (isset($_POST['submforumnewthread']))
				$this->_process_new_thread($params);
			elseif (isset($_POST['submforumnewmessage']))
				$this->_process_new_message($params);
			elseif (isset($_POST['submforumorder']))
				$this->_process_forum_order();
			elseif (isset($_POST['submforumforums']))
				$this->_process_forum_forums();
			elseif (isset($_POST['submforumnieuw']))
				$this->_process_forum_nieuw();
			elseif (isset($_POST['submforumrights']))
				$this->_process_forum_rights($forum);
			elseif (isset($_POST['submforumrightsnieuw']))
				$this->_process_forum_rights_nieuw($forum);
			elseif (isset($_POST['submforumgroups']))
				$this->_process_forum_groups();
			elseif (isset($_POST['submforumgroupsnieuw']))
				$this->_process_forum_groups_nieuw();
			elseif (isset($_POST['submforumgroupsmembers']))
				$this->_process_forum_groups_members();
			elseif (isset($_POST['submforumspecial']))
				$this->_process_forum_special();
			elseif (isset($_POST['submforummodmessage']))
				$this->_process_forum_mod_message($_POST['message_id'], $params);
			elseif (isset($_POST['submforumdelmessage']))
				$this->_process_forum_del_message($_POST['message_id'], $params);
			elseif (isset($_POST['submforummovethread']))
				$this->_process_forum_move_thread($_POST['thread_id'], $_POST['forum_id'], $params);
			elseif (isset($_GET['delmessage']))
				$this->_view_forum_del_message($_GET['delmessage'], $params);
			elseif (isset($_GET['admin']) && isset($_GET['delmember']))
				$this->_process_forum_groups_del_member($_GET['delmember']);
			elseif (isset($_GET['modmessage']))
				$this->_view_mod_message($_GET['modmessage'], $params);
			elseif (isset($_GET['admin']))
				$this->_view_admin($_GET['admin'], $forum);
			elseif (isset($_POST['submforumpollnieuw']))
				$this->_process_forum_poll_nieuw($forum);
			elseif (isset($_POST['submforumpollvote']) && $thread)
				$this->_process_forum_poll_vote($thread);
			elseif (isset($_GET['addpoll']) && $forum)
				$this->get_content('add_poll', $forum, $params);
			elseif ($forum)
				$this->_view_forum($forum, $params);
			elseif ($thread)
				$this->_view_thread($thread, $params);
			else
				$this->_view_fora();
		}
	}
	
	$controller = new ControllerForum();
	$controller->run();
