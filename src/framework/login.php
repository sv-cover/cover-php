<?php
	if (!defined('IN_SITE'))
		return;

	require_once 'src/framework/data/data.php';
	require_once 'src/framework/auth.php';

	function login($email, $pass, $remember = false)
	{
		trigger_error('login() deprecated. Use get_auth()->login()', E_USER_NOTICE);

		if (!get_auth()->login($email, $pass, $remember, $_SERVER['HTTP_USER_AGENT']))
			return false;

		return get_identity()->member()->data;
	}
	
	/** @group Login
	  * Logout a currently logged in member. This means that the 
	  * cookie will be cleared and the member data from the session
	  * will be cleared (thus effectively logging out the member)
	  */
	function logout()
	{
		trigger_error('logout() deprecated. Use get_auth()->logout()', E_USER_NOTICE);

		return get_auth()->logout();
	}

	// Make this function overridable for dump scripts etc.
	if (!function_exists('logged_in'))
	{
		/** @group Login
		  * Check whether a member is currently logged in. When this function
		  * is first called it will check if a member is still in the session,
		  * if so it returns that data. If this is not the case it tries to
		  * login the user from a cookie
		  *
		  * @result false if no member is logged in or the memberdata is
		  * there is a member logged in at the moment
		  */	
		function logged_in($property = null)
		{
			trigger_error('logged_in() deprecated. Use get_auth()->logged_in() or get_identity()->get()', E_USER_NOTICE);

			if (!get_auth()->logged_in())
				return false;

			return $property === null
				? get_identity()->member()
				: get_identity()->get($property);
		}
	}

	function logged_in_member()
	{
		trigger_error('logged_in_member() deprecated. Use get_identity()->member()', E_USER_NOTICE);

		return get_identity()->member();
	}

	function logged_in_as_active_member()
	{
		trigger_error('logged_in_as_active_member() deprecated. Use get_identity()->is_member()', E_USER_NOTICE);

		return get_identity()->is_member();
	}
