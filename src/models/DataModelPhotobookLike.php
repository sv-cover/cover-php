<?php
require_once 'src/framework/data/DataModel.php';
require_once 'src/models/DataModelPhotobook.php';

class DataIterPhotobookLike extends DataIter
{
	static public function fields()
	{
		return ['foto_id', 'lid_id', 'liked_on'];
	}
}

class DataIterLikedPhotobook extends DataIterPhotobook
{
	public function get_id()
	{
		return 'liked';
	}

	public function get_books()
	{
		return array();
	}

	public function get_books_without_metadata()
	{
		return array();
	}

	public function get_photos()
	{
		return $this->model->find('fotos.id IN (' . implode(',', $this->get('photo_ids')) . ')');
	}
}

class DataModelPhotobookLike extends DataModel
{
	public $dataiter = 'DataIterPhotobookLike';

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

	public function get_for_lid(DataIter $member, array $photos = null)
	{
		// Todo: exclude hidden photos from this query? Or should hidden photos
		// mainly be hidden from non-logged-in members, in which case it is not
		// relevant for this query?

		if ($photos === null)
			$query = sprintf('lid_id = %d', $member->get_id());
		elseif (count($photos) === 0)
			return array();
		else
			$query = sprintf('lid_id = %d AND foto_id IN (%s)',
				$member->get_id(),
				implode(',', array_map(function($photo) {
					return $photo->get_id();
				}, $photos)));
		
		$iters = $this->find($query);

		// Add foto_id as index to the array
		return array_combine(
			array_map(function($iter) { return $iter->get('foto_id'); }, $iters),
			$iters);
	}

	public function count_for_photos(array $photos)
	{
		if (count($photos) === 0)
			return array();

		$ids = array_map(function(DataIterPhoto $photo) {
			return $photo->get_id();
		}, $photos);

		$stmt = $this->db->query(sprintf('
			SELECT
				foto_id,
				COUNT(lid_id) as likes
			FROM
				%s
			WHERE
				foto_id IN (%s)
			GROUP BY
				foto_id',
			$this->table, implode(',', $ids)));

		$table = $this->_rows_to_table($stmt, 'foto_id', 'likes');

		foreach ($photos as $photo)
			if (!isset($table[$photo->get_id()]))
				$table[$photo->get_id()] = 0;

		return $table;
	}

	public function get_book(DataIter $member)
	{
		$favorites = array_keys($this->get_for_lid($member));

		return new DataIterLikedPhotobook(get_model('DataModelPhotobook'), -1, array(
			'titel' => __('Liked photos'),
			'has_photos' => count($favorites) > 0,
			'num_photos' => count($favorites),
			'num_books' => 0,
			'read_status' => 'read',
			'datum' => null,
			'parent_id' => 0,
			'photo_ids' => $favorites));
	}
}
