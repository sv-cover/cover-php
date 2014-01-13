<?php
	if (!defined('IN_SITE'))
		return;

	require_once('data.php');

	/** @group Login
	  * Get member data from member id stored in session
	  *
	  * @result an associative array of member data
	  */
	function _member_data_from_session() {
		$model = get_model('DataModelMember');
		$member = $model->get_iter(session_get_member_id());
		
		if (!$member)
			return null;
		
		$member_data = $member->data;
		$member_data['commissies'] = $model->get_commissies($member->get_id());
		
		return $member_data;
	}

	function _commissies_from_id($id){
		$model = get_model('DataModelMember');
		$member = $model -> get_iter($id);

		if(!$member){
			return null;
		}

		$member_commissies = $model -> get_commissies($member -> get_id());

		return $member_commissies;
	}

	function _commissies_from_email($email){
		$model = get_model('DataModelMember');
		$member = $model -> get_from_email($email);

		if(!$member){
			return null;
		}

		$member_commissies = $model -> get_commissies($member -> get_id());

		return $member_commissies;
	}

	/** @group Login
	  * Login a member by email and password. Optionally sets a cookie
	  * to remember the member.
	  * @email the email address of the member to login
	  * @pass the password of the member to login
	  * @remember optional; whether to remember the login in a cookie
	  * if successfully logged in
	  *
	  * @result false if no member couldn't be logged in or the 
	  * memberdata otherwise
	  */
	function login($email, $pass, $remember = false) {
		$model = get_model('DataModelMember');
		$member = $model->login($email, $pass);

		if (!$member)
			return false;

		$session_model = get_model('DataModelSession');

		$timeout = $remember ? '7 DAY' : '1 HOUR';

		$session = $session_model->create($member->get('id'),
			$_SERVER['HTTP_USER_AGENT'],
			$timeout);

		// Set the cookie. Doesn't really matter it is set for such a long time,
		// inactive sessions will be removed from the database and rendered
		// invalid automatically.
		$cookie_time = time() + 24 * 3600 * 31 * 12;

		// Determine the host name for the cookie (try to be as broad as possible so sd.svcover.nl can profit from it)
		if (preg_match('/([^.]+)\.(?:[a-z\.]{2,6})$/i', $_SERVER['HTTP_HOST'], $match))
			$domain = $match[1];
		else if ($_SERVER['HTTP_HOST'] != 'localhost')
			$domain = $_SERVER['HTTP_HOST'];
		else
			$domain = null;

		setcookie('cover_session_id',
			$session->get('session_id'),
			$cookie_time,
			'/', $domain);

		return _member_data_from_session();
	}
	
	/** @group Login
	  * Logout a currently logged in member. This means that the 
	  * cookie will be cleared and the member data from the session
	  * will be cleared (thus effectively logging out the member)
	  */
	function logout()
	{
		$session_model = get_model('DataModelSession');

		$session_model->destroy($_COOKIE['cover_session_id']);

		setcookie('cover_session_id');
	}

	/** @group Login
	  * Check whether a member is currently logged in. When this function
	  * is first called it will check if a member is still in the session,
	  * if so it returns that data. If this is not the case it tries to
	  * login the user from a cookie
	  *
	  * @result false if no member is logged in or the memberdata is
	  * there is a member logged in at the moment
	  */	
	function logged_in()
	{
		static $logged_in = null;
		
		if ($logged_in === null)
		{
			$member_id = session_get_member_id();

			if ($member_id === null)
				return $logged_in = null;

			$logged_in = _member_data_from_session();
		}

		return $logged_in;
	}

	function logged_in_as_active_member()
	{
		$logged_in = logged_in();

		if (!$logged_in)
			return false;

		return in_array($logged_in['type'], array(
			MEMBER_STATUS_LID,
			MEMBER_STATUS_LID_ONZICHTBAAR));
	}

	function session_get_session_id()
	{
		if (!empty($_GET['session_id']))
			return $_GET['session_id'];

		if (!empty($_COOKIE['cover_session_id']))
			return $_COOKIE['cover_session_id'];

		return null;
	}

	function session_get_member_id()
	{
		$session_id = session_get_session_id();

		if ($session_id === null)
			return null;

		$session_model = get_model('DataModelSession');

		$session = $session_model->resume($session_id);

		if (!$session)
			return null;

		return $session->get('member_id');
	}
