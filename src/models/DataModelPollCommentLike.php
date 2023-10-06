<?php
require_once 'src/framework/data/DataModel.php';
require_once 'src/models/DataModelMember.php';
require_once 'src/models/DataModelPollComment.php';

class DataIterPollCommentLike extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'poll_comment_id',
			'member_id',
			'created_on',
		];

	}

	public function get_comment()
	{
		return get_model('DataModelPollComment')->get_iter($this['poll_commen_id']);
	}

	public function get_member()
	{
		if (!empty($this['member_id'])) 
			return get_model('DataModelMember')->get_iter($this['member_id']);
		return null;
	}
}

class DataModelPollCommentLike extends DataModel
{
	public $dataiter = 'DataIterPollCommentLike';

	public function __construct($db)
	{
		parent::__construct($db, 'poll_comment_likes');
	}

	public function get_for_poll_comment(DataIterPollComment $poll_comment)
	{
		$rows = $this->db->query(
			'SELECT *
			   FROM poll_comment_likes
			  WHERE poll_comment_id = :poll_comment_id
			;',
			false,
			[
				'poll_comment_id' => $poll_comment->get_id(),
			],
		);
		return $this->_rows_to_iters($rows);
	}

	public function get_liked_by(DataIterPollComment $poll_comment, DataIterMember $member)
	{
		return $this->db->query_value(
			'SELECT COUNT(id)
			   FROM poll_comment_likes
			  WHERE poll_comment_id = :poll_comment_id
			    AND member_id = :member_id
			;',
			[
				'poll_comment_id' => $poll_comment->get_id(),
				'member_id' => $member->get_id(),
			],
		);
	}

	public function like(DataIterPollComment $poll_comment, DataIterMember $member)
	{
		$this->db->insert($this->table, [
			'poll_comment_id' => $poll_comment->get_id(),
			'member_id' => $member->get_id(),
		]);
	}

	public function unlike(DataIterPollComment $poll_comment, DataIterMember $member)
	{
		$this->db->delete($this->table, 'poll_comment_id = :poll_comment_id AND member_id = :member_id', [
			'poll_comment_id' => $poll_comment->get_id(),
			'member_id' => $member->get_id(),
		]);
	}
}
