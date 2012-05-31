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
		$member = $model->get_iter($_SESSION['member_id']);
		
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
		
		$_SESSION['member_id'] = $member->get('id');
		
		if ($remember) {
			$time = time() + 24 * 3600 * 31 * 12;
			
			setcookie("email", $email, $time);
			setcookie("pass", $pass, $time);
			setcookie("hash", md5($email . $pass . COOKIE_KEY), $time);
		}

		return _member_data_from_session();
	}
	
	/** @group Login
	  * Logout a currently logged in member. This means that the 
	  * cookie will be cleared and the member data from the session
	  * will be cleared (thus effectively logging out the member)
	  */
	function logout() {
		setcookie("email");
		setcookie("pass");
		setcookie("hash");

		unset($_SESSION['member_id']);
	}

	/** @group Login
	  * Tries to login a member from a cookie
	  *
	  * @result false if member couldn't be logged in from a cookie or
	  * memberdata otherwise
	  */
	function _try_login_from_cookie() {
		if (!isset($_COOKIE['email']) || !isset($_COOKIE['pass']) || !isset($_COOKIE['hash']))
			return false;

		if ($_COOKIE['hash'] != md5($_COOKIE['email'] . $_COOKIE['pass'] . COOKIE_KEY))
			return false;
		
		return login($_COOKIE['email'], $_COOKIE['pass'], true);
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
	function logged_in() {
		static $logged_in = null;
		
		if ($logged_in !== null)
			return $logged_in;
		
		$logged_in = false;
		
		if (isset($_SESSION['member_id']))
			$logged_in = _member_data_from_session();

		if (!$logged_in)
			$logged_in = _try_login_from_cookie();
		
		return $logged_in;
	}

?>
