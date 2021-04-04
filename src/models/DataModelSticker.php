<?php

require_once 'src/data/DataModel.php';
require_once 'src/framework/member.php';

class DataIterSticker extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'label',
			'omschrijving',
			'lat',
			'lng',
			'toegevoegd_op',
			'toegevoegd_door',
			'foto'
		];
	}

	public function get_member()
	{
		return $this['toegevoegd_door'] !== null
			? get_model('DataModelMember')->get_iter($this['toegevoegd_door'])
			: null;
	}
}

class DataModelSticker extends DataModel
{
	public $dataiter = 'DataIterSticker';

	public function __construct($db)
	{
		parent::__construct($db, 'stickers');
	}

	public function _row_to_iter($row, $dataiter = null, array $preseed = [])
	{
		$row['lat'] = (double) $row['lat'];
		$row['lng'] = (double) $row['lng'];
		$row['foto'] = $row['foto'] == 't';

		return parent::_row_to_iter($row, $dataiter, $preseed);
	}

	public function getPhoto($sticker)
	{
		$result = $this->db->query_first("SELECT foto FROM {$this->table} WHERE id = " . $sticker->get('id'));

		return $this->db->read_blob($result['foto']);
	}

	public function setPhoto($sticker, $fp)
	{
		$this->db->query("UPDATE {$this->table} SET foto_mtime = NOW(), foto = '" . $this->db->write_blob($fp) . "' WHERE id = " . $sticker->get('id'));
	}

	protected function _generate_query($conditions)
	{
		return "SELECT 
				stickers.id,
				stickers.label,
				stickers.omschrijving,
				stickers.lat,
				stickers.lng,
				stickers.toegevoegd_op,
				stickers.toegevoegd_door,
				stickers.foto IS NOT NULL as foto,
				EXTRACT(EPOCH FROM stickers.foto_mtime) as foto_mtime,
				l.id as toegevoegd_door__id,
				l.voornaam as toegevoegd_door__voornaam,
				l.tussenvoegsel as toegevoegd_door__tussenvoegsel,
				l.achternaam as toegevoegd_door__achternaam,
				l.privacy as toegevoegd_door__privacy
			FROM
				{$this->table}
			LEFT JOIN leden l ON
				l.id = stickers.toegevoegd_door
			" . ($conditions ? " WHERE {$conditions}" : "");
	}

	public function getNearbyStickers($sticker, $limit)
	{
		$rows = $this->db->query(sprintf("SELECT
				s.id,
				s.label,
				s.omschrijving,
				s.lat,
				s.lng,
				s.toegevoegd_op,
				s.toegevoegd_door,
				s.foto IS NOT NULL as foto,
				l.id as toegevoegd_door__id,
				l.voornaam as toegevoegd_door__voornaam,
				l.tussenvoegsel as toegevoegd_door__tussenvoegsel,
				l.achternaam as toegevoegd_door__achternaam,
				l.privacy as toegevoegd_door__privacy,
				(2. * ASIN(
					SQRT(
						(
							POWER(
								SIN(
									RADIANS(
										(c.lat-s.lat) / 2.
									)
								), 
								2)
							)
						+ (
							COS(
								RADIANS(c.lat)
							)
							* COS(
								RADIANS(s.lat)
							)
							* POWER(
								SIN(
									RADIANS(
										(c.lng - s.lng) / 2.
									)
								),
								2
							)
						)
					)
				)) * 6371 as distance -- distance in KM
				FROM {$this->table} s
				RIGHT JOIN {$this->table} c ON c.id = %d
				LEFT JOIN leden l ON l.id = s.toegevoegd_door
				ORDER BY distance ASC
				LIMIT %d", $sticker->get('id'), $limit));

		return $this->_rows_to_iters($rows);
	}

	public function getRecentStickers($limit)
	{
		$rows = $this->find($this->_generate_query('') . " ORDER BY stickers.toegevoegd_op DESC LIMIT " . intval($limit));

		return $this->_rows_to_iters($rows);
	}

	public function getRandomSticker()
	{
		$row = $this->db->query_first($this->_generate_query('') . " ORDER BY RANDOM() DESC LIMIT 1");

		return $this->_row_to_iter($row);
	}
}
