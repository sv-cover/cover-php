<?php

require_once 'src/framework/data/data.php';
require_once 'src/framework/functions.php';

interface IdentityProvider
{
	public function is_member();
	public function is_donor();
	public function member_in_committee($committee = null);
	public function can_impersonate();
	public function member();
	public function get($key, $default_value = null);
}

interface SessionProvider
{
	public function logged_in();
	public function get_session();
}

class GuestIdentityProvider implements IdentityProvider
{
	public function is_member()
	{
		return false;
	}

	public function is_donor()
	{
		return false;
	}

	public function member_in_committee($committee = null)
	{
		return false;
	}

	public function member()
	{
		return null;
	}

	public function get($key, $default_value = null)
	{
		return $default_value;
	}

	public function can_impersonate()
	{
		return false;
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

	public function is_member()
	{
		return $this->session_provider->logged_in()
			&& $this->member()->is_member();
	}

	public function is_donor()
	{
		return $this->session_provider->logged_in()
			&& $this->member()->is_donor();
	}

	public function member_in_committee($committee = null)
	{
		return $this->session_provider->logged_in() && ($committee !== null
			? in_array($committee, $this->member()['committees'])
			: count($this->member()['committees']));
	}

	public function member()
	{
		if (!$this->session_provider->logged_in())
			return null;

		if ($this->member === null) {
			try {
				$this->member = $this->member_model->get_iter($this->session_provider->get_session()['member_id']);
			}
			catch (DataIterNotFoundException $e) {
				// We are logged in as someone who doesn't exist. Let's logout and prevent any further undefined behavior
				$this->session_provider->logout();
				$this->member = null;

				// But also rethrow the exception
				throw $e;
			}
		}

		return $this->member;
	}

	public function get($key, $default_value = null)
	{
		if (!$this->session_provider->logged_in())
			return $default_value;
		elseif (!empty($this->member()[$key]))
			return $this->member()[$key];
		else
			return $default_value;
	}

	public function can_impersonate()
	{
		return false;
	}
}

class ImpersonatingIdentityProvider extends MemberIdentityProvider
{
	protected $override_member;

	protected $override_committees;

	public function member()
	{
		if (!$this->session_provider->logged_in())
			return null;

		if ($this->get_override_member() !== null)
			$member = $this->get_override_member();
		else
			$member = parent::member();

		if ($this->get_override_committees() !== null)
			$member = new DataIterMember($member->model(), $member->get_id(),
				array_merge($member->data, ['committees' => $this->get_override_committees()]));

		return $member;
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

		if (!$session || $session['override_member_id'] === null)
			return null;

		if (!$this->override_member)
			$this->override_member = get_model('DataModelMember')->get_iter($session['override_member_id']);
		
		return $this->override_member;
	}

	public function override_member(DataIterMember $member)
	{
		$this->override_member = $member;

		$session = $this->session_provider->get_session();
		$session['override_member_id'] = $member->get_id();
		$session->update();
	}

	public function reset_member()
	{
		$this->override_member = null;
		
		$session = $this->session_provider->get_session();
		$session['override_member_id'] = null;
		$session->update();
	}

	public function get_override_committees()
	{
		$session = $this->session_provider->get_session();

		if (!$session || $session['override_committees'] === null)
			return null;

		if (!$this->override_committees)
			$this->override_committees = array_map('intval', $session['override_committees'] !== ''
				? explode(';', $session['override_committees'])
				: []);

		return $this->override_committees;
	}

	public function override_committees(array $committee_ids)
	{
		$this->override_committees = array_map('intval', $committee_ids);

		$session = $this->session_provider->get_session();
		$session['override_committees'] = implode(';', $committee_ids);
		$session->update();
	}

	public function reset_committees()
	{
		$this->override_committees = null;

		$session = $this->session_provider->get_session();
		$session['override_committees'] = null;
		$session->update();
	}

	public function is_impersonating()
	{
		return $this->session_provider->logged_in()
			&& ($this->get_override_member() !== null || $this->get_override_committees() !== null);
	}

	public function can_impersonate()
	{
		return true;
	}
}

class CookieSessionProvider implements SessionProvider
{
	/**
	 * @var DataModelSession
	 */
	protected $session_model;

	/**
	 * @var DataIterSession
	 */
	private $session;

	/**
	 * @var bool
	 */
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

		return null;
	}

	protected function resume_session()
	{
		$this->session = $this->session_model->resume($this->get_session_id());
		return $this->logged_in = (bool) $this->session;
	}

	public function login($email, $password, $remember, $application)
	{
		$member = get_model('DataModelMember')->login($email, $password);

		if (!$member)
			return false;

		$session_timeout = $remember ? '7 DAY' : '1 HOUR';

		$this->session = $this->session_model->create(
			$member->get_id(),
			$application,
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
		$session = $this->get_session();

		if (!$session)
			return true;

		$this->session_model->delete($session);

		set_domain_cookie('cover_session_id', null);

		$this->logged_in = false;

		$this->session = null;

		return true;
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

class ConstantSessionProvider implements SessionProvider
{
	/**
	 * @var DataIterSession
	 */
	private $session;

	public function __construct(DataIterSession $session = null)
	{
		$this->session = $session;
	}

	public function logged_in()
	{
		return $this->session !== null;
	}

	public function get_session()
	{
		return $this->session;
	}
}

function get_identity_provider(SessionProvider $authenticator)
{
	if (!$authenticator->logged_in())
		$identity = new GuestIdentityProvider();
	else
		$identity = new MemberIdentityProvider($authenticator);

	if ($identity->member_in_committee(COMMISSIE_EASY))
		$identity = new ImpersonatingIdentityProvider($authenticator);

	return $identity;
}

function get_auth()
{
	static $authenticator;

	if ($authenticator === null)
		$authenticator = new CookieSessionProvider();

	return $authenticator;
}

function get_identity()
{
	static $identity, $identity_is_logged_in;

	$authenticator = get_auth();

	if ($identity === null || $authenticator->logged_in() !== $identity_is_logged_in)
	{
		$identity = get_identity_provider(get_auth());
		$identity_is_logged_in = $authenticator->logged_in();
	}

	return $identity;
}

function nonce_tick()
{
	$nonce_life = get_config_value('nonce_lifetime', 12 * 3600); // 12 hours
	return ceil(time() / ($nonce_life / 2));
}

function nonce_generate($action)
{
	$session = get_auth()->get_session();

	$fields = [
		nonce_tick(),
		$action,
		get_identity()->get('id'),
		$session ? $session->get('id') : ''
	];

	$salt = get_config_value('nonce_salt', null);

	if ($salt === null)
		throw new RuntimeException('No nonce_salt configured in config.inc');

	return hash_hmac('sha1', implode(';', $fields), $salt);
}

function nonce_verify($nonce, $action)
{
	return hash_equals($nonce, nonce_generate($action));
}

function nonce_action_name($action, array $arguments = [])
{
	$arg_strings = array();

	foreach ($arguments as $arg) {
		if ($arg instanceof DataIter)
			$arg = sprintf('%s#%s', get_class($arg), $arg->get('id'));

		$arg_strings[] = strval($arg);
	}

	return implode('_', array_merge([$action], $arg_strings));
}