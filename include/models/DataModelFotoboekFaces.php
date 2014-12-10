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
	public function get_id()
	{
		return sprintf('member_%d', $this->get('member_id'));
	}

	public function get_books()
	{
		return array();
	}

	public function get_photos()
	{
		$condition = sprintf('fotos.id IN (SELECT foto_id FROM foto_faces WHERE lid_id = %d AND deleted = FALSE)',
			$this->get('member_id'));

		return array_reverse($this->model->find($condition), true);
	}
}

class DataModelFotoboekFaces extends DataModel
{
	public $dataiter = 'DataIterFace';

	public function __construct($db)
	{
		parent::__construct($db, 'foto_faces');
	}

	public function get_for_photo(DataIterPhoto $photo)
	{
		return $this->find(sprintf('foto_faces.foto_id = %d', $photo->get_id()));
	}

	public function get_book(DataIter $member)
	{
		$photo_count = $this->db->query_value(sprintf("SELECT COUNT(id) FROM {$this->table} WHERE lid_id = %d", $member->get_id()));

		return new DataIterFacesPhotobook(
				get_model('DataModelFotoboek'), -1, array(
				'titel' => sprintf(__('Foto\'s van %s'), $member->get('voornaam')),
				'has_photos' => $photo_count > 0,
				'num_photos' => $photo_count,
				'num_books' => 0,
				'read_status' => 'read',
				'datum' => null,
				'parent' => 0,
				'member_id' => $member->get_id()));
	}

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
			t.privacy as tagged_by__privacy
			FROM {$this->table}
			LEFT JOIN leden l ON l.id = foto_faces.lid_id
			LEFT JOIN leden t ON t.id = foto_faces.tagged_by
			WHERE foto_faces.deleted = FALSE " . ($where ? ' AND ' . $where : '');
	}

	protected function _delete($table, $iter)
	{
		$this->db->update($table,
			array('deleted' => 'TRUE'),
			$this->_id_string($iter->get_id()),
			array('deleted'));
	}
}
