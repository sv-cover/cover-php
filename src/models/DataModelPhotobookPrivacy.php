<?php

require_once 'src/data/DataModel.php';

class DataModelPhotobookPrivacy extends DataModel
{
	protected $auto_increment = false;

	public function __construct($db)
	{
		parent::__construct($db, 'foto_hidden');
	}

	public function mark_visible(DataIterPhoto $photo, DataIter $member)
	{
		$this->db->query(sprintf("DELETE FROM {$this->table} WHERE foto_id = %d AND lid_id = %d",
			$photo->get_id(), $member->get_id()));
	}

	public function mark_hidden(DataIterPhoto $photo, DataIter $member)
	{
		try {
			$this->db->query(sprintf("INSERT INTO {$this->table} (foto_id, lid_id) VALUES (%d, %d)",
				$photo->get_id(), $member->get_id()));
		}
		catch (RuntimeException $e) {
			// Duplicate key constraint, no problem!
		}
	}

	public function is_visible(DataIterPhoto $photo, DataIter $member)
	{
		return (bool) $this->db->query_value(
			sprintf("SELECT COUNT(*) = 0 FROM {$this->table} WHERE foto_id = %d AND lid_id = %d",
				$photo->get_id(), $member->get_id()));
	}

	public function get_visibility_for_photos(array $photos, DataIter $user)
	{
		if (count($photos) === 0)
			return array();

		$ids = array_map(function(DataIter $photo) { return $photo->get_id(); }, $photos);

		$rows = $this->db->query(sprintf("SELECT foto_id FROM {$this->table} WHERE lid_id = %d AND foto_id IN(%s)",
			$user->get_id(), implode(',', $ids)));

		$privacy = array_combine($ids, array_fill(0, count($ids), true));

		foreach ($rows as $row)
			$privacy[$row['foto_id']] = false;

		return $privacy;
	}
}
