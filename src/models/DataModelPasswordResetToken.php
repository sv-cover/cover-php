<?php

require_once 'src/data/DataModel.php';
require_once 'src/framework/router.php';

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataIterPasswordResetToken extends DataIter
{
	static public function fields()
	{
		return [
			'key',
			'member_id',
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
		return $router->generate('password', ['reset_token' => $this['key']], UrlGeneratorInterface::ABSOLUTE_URL);
	}
}

class DataModelPasswordResetToken extends DataModel
{
	public $dataiter = 'DataIterPasswordResetToken';

	public function __construct($db)
	{
		parent::__construct($db, 'password_reset_tokens', 'key');
	}

	public function create_token_for_member(DataIterMember $member)
	{
		$token = $this->new_iter([
			'key' => randstr(40),
			'member_id' => $member['id'],
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