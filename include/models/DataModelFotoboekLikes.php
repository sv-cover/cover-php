<?php
require_once 'data/DataModel.php';

class DataModelFotoboekLikes extends DataModel
{
	public function __construct($db)
	{
		parent::__construct($db, 'foto_likes');
	}

	public function like(DataIter $photo, $lid_id)
	{
		$foto_id = $photo->get_id();

		$this->db->insert($this->table, compact('foto_id', 'lid_id'));
	}

	public function unlike(DataIter $photo, $lid_id)
	{
		$this->db->delete($this->table, sprintf(
			'foto_id = %d AND lid_id = %d', $photo->get_id(), $lid_id));
	}

	public function toggle(DataIter $photo, $lid_id)
	{
		if ($this->is_liked($photo, $lid_id))
			$this->unlike($photo, $lid_id);
		else
			$this->like($photo, $lid_id);
	}

	public function is_liked(DataIter $photo, $lid_id)
	{
		$result = $this->db->query_first(sprintf('
			SELECT
				COUNT(1) as liked
			FROM
				%s
			WHERE
				foto_id = %d
				AND lid_id = %d',
			$this->table, $photo->get_id(), $lid_id));

		return $result['liked'] > 0;
	}

	public function get_for_photo(DataIter $photo)
	{
		return $this->find(sprintf('foto_id = %d', $photo->get('id')));
	}

	public function get_for_lid(DataIter $member)
	{
		return $this->find(sprintf('lid_id = %d', $member->get('id')));
	}
}
