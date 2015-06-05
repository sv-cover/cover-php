<?php

require_once 'include/data.php';
require_once 'include/functions.php';

interface IdentityProvider
{
	public function member_is_active();
	public function member_in_committee($committee = null);
	public function get_member();
	public function get($key, $default_value = null);
}

class GuestIdentityProvider implements IdentityProvider
{
	public function member_is_active()
	{
		return false;
	}

	public function member_in_committee($committee = null)
	{
		return false;
	}

	public function get_member()
	{
		return null;
	}

	public function get($key, $default_value = null)
	{
		return $default_value;
	}
}

class MemberIdentityProvider implements IdentityProvider
{
	protected $session_provider;

	protected $member_model;
	
	protected $member;

	public function __construct(SessionProvider $session_provider)
	{
		$this->session_provider = $session_provider;

		$this->member_model = get_model('DataModelMember');
	}

	public function member_is_active()
	{
		return $this->session_provider->logged_in()
			&& in_array($this->get_member()->get('type'), [
				MEMBER_STATUS_LID,
				MEMBER_STATUS_LID_ONZICHTBAAR
			]);
	}

	public function member_in_committee($committee = null)
	{
		return $this->session_provider->logged_in() && ($committee !== null
			? in_array($committee, $this->get_member()->get('committees'))
			: count($this->get_member()->get('committees')));
	}

	public function get_member()
	{
		if (!$this->session_provider->logged_in())
			return null;

		if (!$this->member)
			$this->member = $this->member_model->get_iter($this->session_provider->get_session()->get('member_id'));

		return $this->member;
	}

	public function get($key, $default_value = null)
	{
		if (!$this->session_provider->logged_in())
			return $default_value;
		elseif ($this->get_member()->has($key))
			return $this->get_member()->get($key);
		else
			return $default_value;
	}
}

class ImpersonatingIdentityProvider extends MemberIdentityProvider
{
	protected $override_member;

	protected $override_committees;

	public function get_member()
	{
		if (!$this->session_provider->logged_in())
			return null;

		if ($this->session_provider->get_session()->has('override_member_id'))
			return $this->get_override_member();
		else
			return parent::get_member();

		return $this->override_member;
	}

	public function get($key, $default_value = null)
	{
		if (!$this->session_provider->logged_in())
			return $default_value;
		elseif ($this->get_member()->has($key))
			return $this->get_member()->get($key);
		else
			return $default_value;
	}

	public function member_in_committee($committee = null)
	{
		if ($this->get_override_committees() === null)
			return parent::member_in_committee($committee);

		return $committee !== null
			? in_array($committee, $this->get_override_committees())
			: count($this->get_override_committees());
	}

	public function get_override_member()
	{
		$session = $this->session_provider->get_session();

		if (!$session || !$session->has('override_member_id'))
			return null;

		if (!$this->override_member)
			$this->override_member = get_model('DataModelMember')->get_iter($session->get('override_member_id'));
		
		return $this->override_member;
	}

	public function override_member(DataIterMember $member)
	{
		$this->override_member = $member;

		$session = $this->session_provider->get_session();
		$session->set('override_member_id', $member->get_id());
		$session->update();
	}

	public function reset_member()
	{
		$this->override_member = null;
		
		$session = $this->session_provider->get_session();
		$session->set('override_member_id', null);
		$session->update();
	}

	public function get_override_committees()
	{
		$session = $this->session_provider->get_session();

		if (!$session || !$session->has('override_committees'))
			return null;

		if (!$this->override_committees)
			$this->override_committees = array_map('intval', $session->get('override_committees') !== ''
				? explode(';', $session->get('override_committees'))
				: []);

		return $this->override_committees;
	}

	public function override_committees(array $committee_ids)
	{
		$this->override_committees = array_map('intval', $committee_ids);

		$session = $this->session_provider->get_session();
		$session->set('override_committees', implode(';', $committee_ids));
		$session->update();
	}

	public function reset_committees()
	{
		$this->override_committees = null;

		$session = $this->session_provider->get_session();
		$session->set('override_committees', null);
		$session->update();
	}
}

class SessionProvider
{
	protected $session_model;

	private $session;

	private $logged_in;

	public function __construct()
	{
		$this->session_model = get_model('DataModelSession');
	}

	protected function get_session_id()
	{
		if (!empty($_GET['session_id']))
			return $_GET['session_id'];

		if (!empty($_COOKIE['cover_session_id']))
			return $_COOKIE['cover_session_id'];

		$auto_login_ips = get_config_value('auto_login', array());

		if (isset($_SERVER['REMOTE_ADDR'], $auto_login_ips[$_SERVER['REMOTE_ADDR']]))
			return $auto_login_ips[$_SERVER['REMOTE_ADDR']];

		return null;
	}

	protected function resume_session()
	{
		$this->session = $this->session_model->resume($this->get_session_id());
		return $this->logged_in = (bool) $this->session;
	}

	public function login($email, $password, $remember = false)
	{
		$member = get_model('DataModelMember')->login($email, $password);

		if (!$member)
			return false;

		$session_timeout = $remember ? '7 DAY' : '1 HOUR';

		$this->session = $this->session_model->create(
			$member->get_id(),
			$_SERVER['HTTP_USER_AGENT'],
			$session_timeout);

		// Set the cookie. Doesn't really matter it is set for such a long time,
		// inactive sessions will be removed from the database and rendered
		// invalid automatically. A low value will cause people to be logged out.
		$cookie_time = time() + 24 * 3600 * 31 * 12;

		set_domain_cookie('cover_session_id',
			$this->session->get('session_id'),
			$cookie_time);

		return $this->logged_in = true;
	}

	public function logout()
	{
		$this->session_model->delete($this->get_session());

		set_domain_cookie('cover_session_id', null);

		$this->logged_in = false;

		$this->session = null;
	}

	public function logged_in()
	{
		if ($this->logged_in === null)
			$this->resume_session();

		return $this->logged_in;
	}

	public function get_session()
	{
		return $this->logged_in() ? $this->session : null;
	}
}

function get_auth()
{
	static $authenticator;

	if ($authenticator === null)
		$authenticator = new SessionProvider();

	return $authenticator;
}

function get_identity()
{
	static $identity;

	if ($identity === null)
	{
		$authenticator = get_auth();

		if (!$authenticator->logged_in())
			$identity = new GuestIdentityProvider();
		else
			$identity = new MemberIdentityProvider($authenticator);

		if ($identity->member_in_committee(COMMISSIE_EASY))
			$identity = new ImpersonatingIdentityProvider($authenticator);
	}

	return $identity;
}
