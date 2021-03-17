<?php

require_once 'include/data/DataModel.php';
require_once 'include/router.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataIterEmailConfirmationToken extends DataIter
{
	static public function fields()
	{
		return [
			'key',
			'member_id',
			'email',
			'created_on'
		];
	}

	public function get_member()
	{
		return get_model('DataModelMember')->get_iter($this['member_id']);
	}

	public function get_link()
	{
		$router = get_router();
		return $router->generate('profile', ['view' => 'confirm_email', 'token' => $this['key']], UrlGeneratorInterface::ABSOLUTE_URL);
	}
}

class DataModelEmailConfirmationToken extends DataModel
{
	public $dataiter = 'DataIterEmailConfirmationToken';

	public function __construct($db)
	{
		parent::__construct($db, 'email_confirmation_tokens', 'key');
	}

	public function create_token(DataIterMember $member, $email)
	{
		$token = $this->new_iter([
			'key' => randstr(40),
			'member_id' => $member['id'],
			'email' => $email,
			'created_on' => new DateTime()
		]);

		$this->insert($token);

		return $token;
	}

	public function invalidate_all(DataIterMember $member)
	{
		$this->db->query("DELETE FROM {$this->table} WHERE member_id = :member_id", false, [':member_id' => $member['id']]);
	}
}