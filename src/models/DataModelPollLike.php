<?php
require_once 'src/framework/data/DataModel.php';
require_once 'src/models/DataModelMember.php';
require_once 'src/models/DataModelPoll.php';

class DataIterPollLike extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'poll_id',
			'member_id',
			'created_on',
		];

	}

	public function get_poll()
	{
		return get_model('DataModelPoll')->get_iter($this['poll_id']);
	}

	public function get_member()
	{
		if (!empty($this['member_id'])) 
			return get_model('DataModelMember')->get_iter($this['member_id']);
		return null;
	}
}

class DataModelPollLike extends DataModel
{
	public $dataiter = 'DataIterPollLike';

	public function __construct($db)
	{
		parent::__construct($db, 'poll_likes');
	}

	public function get_for_poll(DataIterPoll $poll)
	{
		$rows = $this->db->query(
			'SELECT *
			   FROM poll_likes
			  WHERE poll_id = :poll_id
			;',
			false,
			[
				'poll_id' => $poll->get_id(),
			],
		);
		return $this->_rows_to_iters($rows);
	}

	public function get_liked_by(DataIterPoll $poll, DataIterMember $member)
	{
		return $this->db->query_value(
			'SELECT COUNT(id)
			   FROM poll_likes
			  WHERE poll_id = :poll_id
			    AND member_id = :member_id
			;',
			[
				'poll_id' => $poll->get_id(),
				'member_id' => $member->get_id(),
			],
		);
	}

	public function like(DataIterPoll $poll, DataIterMember $member)
	{
		$this->db->insert($this->table, [
			'poll_id' => $poll->get_id(),
			'member_id' => $member->get_id(),
		]);
	}

	public function unlike(DataIterPoll $poll, DataIterMember $member)
	{
		$this->db->delete($this->table, 'poll_id = :poll_id AND member_id = :member_id', [
			'poll_id' => $poll->get_id(),
			'member_id' => $member->get_id(),
		]);
	}
}
