<?php

require_once 'include/data/DataModel.php';

class DataIterFace extends DataIter
{
	public function get_position()
	{
		return array(
			'x' => 100 * $this->get('x'),
			'y' => 100 * $this->get('y'),
			'w' => 100 * $this->get('w'),
			'h' => 100 * $this->get('h')
		);
	}
}

class DataIterFacesPhotobook extends DataIterPhotobook
{
	private $_cached_photos = null;

	/**
	 * Add a special id to this photo book, consisting of 'member_' and the 
	 * member ids shown in this book.
	 * 
	 * @override
	 * @return string
	 */
	public function get_id()
	{
		return sprintf('member_%s', implode('_', $this->get('member_ids')));
	}

	/**
	 * Override DataIterPhotobook::get_books because this special photo book
	 * has no child books.
	 *
	 * @override
	 * @return DataIterPhotobook[]
	 */
	public function get_books()
	{
		return array();
	}

	/**
	 * Get all photos with the faces of the members of this photo book. Note 
	 * that this method caches the query results in $this->_cached_photos so
	 * changing the member_ids value after calling this method once causes
	 * undefined behavior.
	 *
	 * @override
	 * @return DataIterPhoto[] photos with all members tagged ordered from
	 * newest to oldest.
	 */
	public function get_photos()
	{
		if ($this->_cached_photos !== null)
			return $this->_cached_photos;

		$conditions = array();

		foreach ($this->get('member_ids') as $member_id)
			$conditions[] = sprintf('fotos.id IN (SELECT foto_id FROM foto_faces WHERE lid_id = %d AND deleted = FALSE)', $member_id);
		
		// Find which photos should not be shown for this set of members
		$hidden = get_model('DataModelFotoboekPrivacy')->find(sprintf('lid_id IN(%s)', implode(',', $this->get('member_ids'))));
		
		// Also grab the ids of all the photos which should actually be hidden (e.g. are not of the logged in member)
		$excluded_ids = array_filter(array_map(function($iter) { return logged_in('id') != $iter->get('lid_id') ? $iter->get('foto_id') : false; }, $hidden));

		// If there are any photos that should be hidden, exclude them from the query
		if (count($excluded_ids) > 0)
			$conditions[] = sprintf('fotos.id NOT IN (%s)', implode(',', $excluded_ids));
		
		$photos = $this->model->find(implode("\nAND ", $conditions));

		return $this->_cached_photos = array_reverse($photos);
	}

	/**
	 * @override
	 */
	public function count_photos()
	{
		return count($this->get_photos());
	}
}

class DataModelFotoboekFaces extends DataModel
{
	public $dataiter = 'DataIterFace';

	public function __construct($db)
	{
		parent::__construct($db, 'foto_faces');
	}

	/**
	 * Find all tags/faces for a given photo.
	 * 
	 * @var DataIterPhoto $photo
	 * @return DataIterFace[] faces
	 */
	public function get_for_photo(DataIterPhoto $photo)
	{
		return $this->find(sprintf('foto_faces.foto_id = %d', $photo->get_id()));
	}

	/**
	 * Get photo book of all photos in which each photo all $members are tagged together.
	 *
	 * @var DataIterMember[] $members
	 * @return DataIterFacesPhotobook
	 */
	public function get_book(array $members)
	{
		foreach ($members as $member)
			assert('$member instanceof DataIterMember');

		return new DataIterFacesPhotobook(
				get_model('DataModelFotoboek'), -1, array(
				'titel' => sprintf(__('Foto\'s van %s'),
					implode(__(' en '), array_map(function($member) { return $member->get('voornaam'); }, $members))),
				'num_books' => 0,
				'read_status' => 'read',
				'datum' => null,
				'parent' => 0,
				'member_ids' => array_map(function($member) { return $member->get_id(); }, $members)));
	}

	/**
	 * Start a python process in the background to detect faces in the photos.
	 *
	 * @var DataIterPhoto[] $photos
	 * @return int pid
	 */
	public function refresh_faces(array $photos)
	{
		$photo_ids = array();

		foreach ($photos as $photo) {
			assert('$photo instanceof DataIterPhoto');
			$photo_ids[] = $photo->get_id();
		}

		$command = sprintf('%s opt/facedetect/suggest_faces.py %s >> %s 2>&1 & echo $!',
			escapeshellarg(get_config_value('path_to_python', 'python')),
			implode(' ', $photo_ids),
			escapeshellarg(get_config_value('path_to_suggest_faces_log', '/dev/null')));
		
		$pid = shell_exec($command);

		if (is_null($pid))
			throw new Exception("Could not start suggest_faces process");

		return intval(rtrim($pid, " "));
	}

	/**
	 * @override
	 */
	protected function _generate_query($where)
	{
		return "SELECT
			foto_faces.id,
			foto_faces.foto_id,
			foto_faces.x,
			foto_faces.y,
			foto_faces.w,
			foto_faces.h,
			foto_faces.lid_id,
			foto_faces.tagged_by,
			foto_faces.custom_label,
			l.id as lid__id,
			l.voornaam as lid__voornaam,
			l.tussenvoegsel as lid__tussenvoegsel,
			l.achternaam as lid__achternaam,
			l.privacy as lid__privacy,
			t.voornaam as tagged_by__voornaam,
			t.tussenvoegsel as tagged_by__tussenvoegsel,
			t.achternaam as tagged_by__achternaam,
			t.privacy as tagged_by__privacy,
			(SELECT COUNT(1)
				FROM foto_hidden f_h
				WHERE
					f_h.foto_id = foto_faces.foto_id
					AND f_h.lid_id = foto_faces.lid_id
			) as hidden
			FROM {$this->table}
			LEFT JOIN leden l ON l.id = foto_faces.lid_id
			LEFT JOIN leden t ON t.id = foto_faces.tagged_by
			WHERE foto_faces.deleted = FALSE " . ($where ? ' AND ' . $where : '');
	}

	/**
	 * @override
	 */
	protected function _delete($table, DataIter $iter)
	{
		$this->db->update($table,
			array('deleted' => 'TRUE'),
			$this->_id_string($iter->get_id()),
			array('deleted'));
	}
}
