<?php
namespace App\Controller;

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/form.php';
require_once 'include/controllers/Controller.php';

/**
 * Warning & Todo: The terms 'topic' and 'thread' are used 
 * interchangeably throughout this part of the code. We should
 * probably refactor it a bit to choose one of them (I prefer 'topic') 
 * ~ Jelmer, 2017-01-15
 */

class ForumController extends \Controller
{
	const MINIMUM_POLL_OPTION_COUNT = 3;

	const DEFAULT_POLL_OPTION_COUNT = 5;

	const MAXIMUM_POLL_OPTION_COUNT = 10;

    protected $view_name = 'forum';

    public function __construct($request, $router)
    {
		$this->model = get_model('DataModelForum');

	   parent::__construct($request, $router);
	}

	private function _assert_access(\DataIterForum $forum, $authorid, $acl)
	{
		$authorid = intval($authorid);
		
		if (!$this->model->check_acl($forum, $acl, get_identity()))
			throw new \UnauthorizedException('You do not have the right permissions for this action.');

		if ($authorid != -1 && !$this->model->check_acl_commissie($forum, $acl, $authorid))
			throw new \UnauthorizedException('You do not have the right permissions for this action.');

		if ($authorid > 0 && !get_identity()->member_in_committee($authorid))
			throw new \UnauthorizedException('You do not have the right permissions for this action.');
	}

	private function _is_admin()
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}
	
	private function _assert_admin()
	{
		if (!$this->_is_admin())
			throw new \UnauthorizedException('You need to be a member of the board or the AC/DCee for this action.');
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
			case \DataModelForum::TYPE_PERSON:
				return $this->_is_admin() || get_identity()->member()->get('id') == $author_id ? $value : false;

			case \DataModelForum::TYPE_COMMITTEE:
				return $this->_is_admin() || get_identity()->member_in_committee($author_id) ? $value : false;

			case \DataModelForum::TYPE_GROUP:
				try {
					get_model('DataModelForum')->get_group($author_id);
				} catch (\DataIterNotFoundException $e) {
					return false;
				}
				break;

			default:
				return false;
		}
	}

	private function _create_thread(\DataIterForum $forum)
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

		$thread = $forum->new_thread();
		$thread->set_all($thread_data);
		
		$message = $thread->new_message();
		$message->set_all($message_data);

		if (count($errors) > 0)
			return $this->view->render_thread_form($forum, $thread, $message, $errors);

		// Create new thread with given subject
		$this->model->insert_thread($thread, $message);
		
		// run_message_single redirects to the correct message in a thread
		return $this->run_message_single($message);
	}

	private function _create_poll(\DataIterForum $forum)
	{
		$poll_model = get_model('DataModelPoll');

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

		$poll = $poll_model->new_poll($forum);
		$poll->set_all($thread_data);
		
		$message = $poll->new_message();
		$message->set_all($message_data);

		$options = [];

		$poll_errors = [];

		foreach ($_POST['poll_option'] as $i => $label)
		{
			// Mark an empty option as an error, but process it anyway.
			// If there are enough non-empty options, it is fine.
			if (empty($label) && $i + 1 < self::MINIMUM_POLL_OPTION_COUNT)
				$poll_errors[] = 'poll_option[' . $i . ']';

			$option = $poll->new_poll_option();
			$option['optie'] = $label;
			$options[] = $option;
		}

		$valid_options = array_filter($options, function($option) {
			return !empty($option['optie']);
		});

		if (count($valid_options) > self::MAXIMUM_POLL_OPTION_COUNT)
			$errors[] = 'poll_option_count';

		if (count($valid_options) < self::MINIMUM_POLL_OPTION_COUNT)
			$errors[] = 'poll_option_count';

		if (count($errors) > 0)
			return $this->view->render_poll_form($forum, $poll, $message, $options, array_merge($errors, $poll_errors));

		// Create new poll/thread with given subject
		$poll_model->insert_poll($poll, $message, $valid_options);
		
		// run_message_single redirects to the correct message in a thread
		return $this->run_message_single($message);
	}

	private function _process_forum_poll_vote(\DataIterForumThread $thread)
	{
		if (!isset($_POST['optie']) || $_POST['optie'] === '')
			return $this->_view_thread($thread, null);
		
		$poll_model = get_model('DataModelPoll');
		
		if ($poll_model->voted($thread))
			return $this->_view_thread($thread, null);

		$poll_model->vote($_POST['optie']);
		
		return $this->view->redirect($_SERVER['HTTP_REFERER']);
	}
	
	private function _assert_may_edit_message(\DataIterForumMessage $message)
	{
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException('You need to be logged in to edit a message.');
		
		if (!$message->editable())
			throw new \UnauthorizedException('You do not have permission to edit this message.');
	}
    
    /*
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
                // Header
                if ($info[0] == '-') {
                    // New header
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
            case DataModelForum::TYPE_ANONYMOUS: // Everyone
                $uid = -1;
            break;
            case DataModelForum::TYPE_PERSON: // Member
                $id = intval(get_post('member'));

                if ($id != 0) {
                    $member_model = get_model('DataModelMember');
                    $member = $member_model->get_iter($id);
                
                    if ($member)
                        $uid = $id;
                }
            break;
            case DataModelForum::TYPE_COMMITTEE: // Commissie
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
            case DataModelForum::TYPE_GROUP: // Group
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
        
        // Check group
        $group = $this->model->get_group(get_post('guid'));
        
        if (!$group)
            return $this->view->render_admin(null, array('sub' => 'groups', 'errors' => array('guid')));
        
        switch (intval(get_post('type'))) {
            case DataModelForum::TYPE_ANONYMOUS:
                $uid = -1;
            break;
            case DataModelForum::TYPE_PERSON:
                $id = intval(get_post('member'));

                if ($id != 0) {
                    $member_model = get_model('DataModelMember');
                    $member = $member_model->get_iter($id);
                    $uid = $member->get('id');
                }
            break;
            case DataModelForum::TYPE_COMMITTEE:
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
    */

	private function _create_message(\DataIterForumMessage $message, array $data, array &$errors)
	{
		$message_data = check_values([
			'message',
			['name' => 'unified_author', 'function' => [$this, '_check_unified_author']]
		], $errors, $data);

		if (count($errors) > 0)
			return false;
		
		$message->set_all($message_data);
		$message['date'] = new \DatabaseLiteral('CURRENT_TIMESTAMP');

		$this->model->insert_message($message);

		return true;
	}
	
	private function _update_message(\DataIterForumMessage $message, array $data, array &$errors)
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
	
	public function run_forum_index(\DataIterForum $forum)
	{
		if (!get_policy($forum)->user_can_read($forum))
			throw new \UnauthorizedException('You are not allowed to read this forum');

		if (get_identity()->member())
			$this->model->set_forum_session_read($forum, get_identity()->member());
		
		return $this->view->render_forum($forum);
	}

	public function run_forum_update(\DataIterForum $forum)
	{
		if (!get_policy($forum)->user_can_update($forum))
			throw new \UnauthorizedException('You are not allowed to update the name or description of this forum');

		if ($this->_form_is_submitted('update_forum', $forum))
		{
			if (!empty($_POST['name']))
				$forum['name'] = $_POST['name'];
			
			if (isset($_POST['description']))
				$forum['description'] = $_POST['description'];

			$forum->update();
		}

		return $this->view->render_forum_form($forum);
	}
	
	public function run_thread_index(\DataIterForumThread $thread)
	{
		if (!get_policy($thread)->user_can_read($thread))
			throw new \UnauthorizedException('You are not allowed to read this thread');

		if ($member = get_identity()->member())
		{
			$this->model->set_forum_session_read($thread['forum'], $member);

			$this->model->mark_read($thread, $member);
		}
		
		return $this->view->render_thread($thread);
	}

	public function run_thread_create(\DataIterForum $forum)
	{
		$empty_thread = $forum->new_thread();

		if (!get_policy($empty_thread)->user_can_create($empty_thread))
			throw new \UnauthorizedException('You are not allowed to create a new thread in this forum');
		
		if ($this->_form_is_submitted('create_thread', $forum))
			return $this->_create_thread($forum);

		$empty_message = new \DataIterForumMessage($this->model, null, []);

		return $this->view->render_thread_form($forum, $empty_thread, $empty_message, array());
	}

	public function run_thread_delete(\DataIterForumThread $thread)
	{
		if (!get_policy($thread)->user_can_delete($thread))
			throw new \UnauthorizedException('You are not allowed to delete this thread.');

		if ($this->_form_is_submitted('delete_thread', $thread))
			if ($this->model->delete_thread($thread))
				return $this->view->redirect($this->generate_url('forum', ['forum' => $thread['forum_id']]));

		return $this->view->render_thread_delete($thread);
	}

	public function run_thread_move(\DataIterForumThread $thread)
	{
		if (!get_policy($thread)->user_can_update($thread))
			throw new \UnauthorizedException('You are not allowed to move this thread.');

		if ($this->_form_is_submitted('move_thread', $thread))
		{
			$target_forum = $this->model->get_iter($_POST['forum_id']);

			// You have to have the rights to create a thread in the target forum
			$test_thread = $target_forum->new_thread();
			if (!get_policy($test_thread)->user_can_create($test_thread))
				throw new \UnauthorizedException('You do not have the rights to create a new thread in the target forum');

			$this->model->move_thread($thread, $target_forum);
		}

		return $this->view->redirect($this->generate_url('forum', ['thread' => $thread['id']]));
	}

	public function run_thread_reply(\DataIterForumThread $thread)
	{
		$message = $thread->new_message();

		if (!get_policy($message)->user_can_create($message))
			throw new \UnauthorizedException('You are not allowed to create new messages in this thread.');

		if (isset($_GET['quote_message'])) {
			try {
				$quoted_message = $this->model->get_message($_GET['quote_message']);
				$quoted_author = $this->model->get_author_info($quoted_message, 'author');
				$message['message'] = sprintf("[quote=%s]%s[/quote]\n\n", $quoted_author['name'], $quoted_message['message']);
			} catch (\DataIterNotFoundException $e) {
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
	
	public function run_message_update(\DataIterForumMessage $message)
	{
		if (!get_policy($message)->user_can_update($message))
			throw new \UnauthorizedException('You are not allowed to modify this message.');

		$errors = [];

		if ($this->_form_is_submitted('update_message', $message))
			if ($this->_update_message($message, $_POST, $errors))
				return $this->run_message_single($message);

		return $this->view->render_message_form($message, $errors);
	}

	public function run_message_delete(\DataIterForumMessage $message)
	{
		if (!get_policy($message)->user_can_delete($message))
			throw new \UnauthorizedException('You are not allowed to delete this message.');

		$thread = $this->model->get_thread($message['thread_id']);

		if ($message->is_only_message())
			return $this->run_thread_delete($thread);

		if ($this->_form_is_submitted('delete_message', $message))
			if ($this->model->delete_message($message))
				return $this->view->redirect($this->generate_url('forum', ['thread' => $thread['id'], 'page' => $thread['num_thread_pages']]));

		return $this->view->render_message_delete($message);
	}

	public function run_message_single(\DataIterForumMessage $message)
	{
		return $this->view->redirect(sprintf('%s#p%d', $this->generate_url('forum', ['thread' => $message['thread_id'], 'page' => $message['thread_page']]), $message['id']));
	}

	public function run_poll_create(\DataIterForum $forum)
	{
		$poll_model = get_model('DataModelPoll');

		$poll = $poll_model->new_poll($forum);

		if (!get_policy($poll)->user_can_create($poll))
			throw new \UnauthorizedException('You are not allowed to create a new poll in this forum');
		
		if ($this->_form_is_submitted('create_poll', $forum))
			return $this->_create_poll($forum);

		$message = $poll->new_message();

		$options = [];

		for ($i = 0; $i < self::DEFAULT_POLL_OPTION_COUNT; ++$i)
			$options[] = $poll->new_poll_option();

		return $this->view->render_poll_form($forum, $poll, $message, $options, array());
	}

	public function run_poll_vote(\DataIterForumThread $thread)
	{
		$poll_model = get_model('DataModelPoll');

		$poll = $poll_model->from_thread($thread);

		if (!get_policy($poll)->user_can_vote($poll))
			throw new \UnauthorizedException('You are not allowed to cast your vote (again) for this poll');

		if ($this->_form_is_submitted('poll_vote', $poll))
		{
			if (!isset($_POST['option']))
				throw new \InvalidArgumentException('Missing option post parameter');

			if (!get_policy($poll_model)->user_can_vote($poll))
				throw new \UnauthorizedException('You cannot vote for this poll');

			$options = $poll['options'];

			$option = array_find($poll['options'], function($option) {
				return $option['id'] == $_POST['option'];
			});

			if ($option === null)
				throw new \InvalidArgumentException('Poll option not found among the poll options');

			if (!$poll_model->vote($option, get_identity()->member()))
				throw new \LogicException('Could not increment vote. Invalid poll option somehow?');
		}

		return $this->view->redirect($this->generate_url('forum', ['thread' => $thread['id']]));
	}
	
	public function run_index()
	{
		// Set last visit for all fora to current time
		if ($member = get_identity()->member())
			$this->model->update_last_visit($member);

		$forums = $this->model->get();

		return $this->view->render_index($forums);
	}
	
	public function run_preview()
	{
		return $this->view->render_preview($_POST['message']);
	}
	
	private function _process_forum_move_thread($thread_id, $forum_id)
	{
		if (!get_auth()->logged_in())
			throw new \UnauthorizedException('Log in to move threads');

		$thread = $this->model->get_thread($thread_id);
		
		$forum = $this->model->get_iter($forum_id);
		
		if (!$thread->editable())
			throw new \UnauthorizedException('You do not have permission to move threads');
		
		$thread->forum = $forum->get('id');
		$this->model->update_thread($thread);
		
		return $this->view->redirect($this->generate_url('forum', ['thread' => $thread->get('id')]));
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
			elseif ($view == 'delete')
				return $this->run_message_delete($message);
			else
				return $this->run_message_single($message);
		}
		elseif (isset($_GET['thread'])) {
			$thread = $this->model->get_thread($_GET['thread']);

			if ($view == 'reply')
				return $this->run_thread_reply($thread);
			elseif ($view == 'move')
				return $this->run_thread_move($thread);
			elseif ($view == 'delete')
				return $this->run_thread_delete($thread);
			elseif ($view == 'vote')
				return $this->run_poll_vote($thread);
			else
				return $this->run_thread_index($thread);
		}
		elseif (isset($_GET['forum'])) {
			$forum = $this->model->get_iter($_GET['forum'], $admin ? -1 : \DataModelForum::ACL_READ);

			if ($view == 'create')
				return $this->run_thread_create($forum);
			elseif ($view == 'create_poll')
				return $this->run_poll_create($forum);
			elseif ($view == 'update')
				return $this->run_forum_update($forum);
			else
				return $this->run_forum_index($forum);
		}
		elseif ($view == 'preview')
			return $this->run_preview();
		else
			return $this->run_index();
	}
}
