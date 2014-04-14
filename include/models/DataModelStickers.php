<?php

require_once 'data/DataModel.php';

class DataModelStickers extends DataModel
{
	public function __construct($db)
	{
		parent::__construct($db, 'stickers');
	}

	public function _row_to_iter($row)
	{
		$row['lat'] = (double) $row['lat'];
		$row['lng'] = (double) $row['lng'];
		$row['foto'] = $row['foto'] == 't';

		return parent::_row_to_iter($row);
	}

	public function addSticker($label, $omschrijving, $lat, $lng)
	{
		$toegevoegd_op = date('Y-m-d');

		$toegevoegd_door = logged_in('id');

		$data = compact('label', 'omschrijving', 'lat', 'lng', 'toegevoegd_op', 'toegevoegd_door');

		$iter = new DataIter($this->model, -1, $data);

		return $this->insert($iter, true);
	}

	public function getPhoto($sticker)
	{
		$result = $this->db->query_first("SELECT foto FROM {$this->table} WHERE id = " . $sticker->get('id'));

		return pg_unescape_bytea($result['foto']);
	}

	public function setPhoto($sticker, $fp)
	{
		$data = stream_get_contents($fp);

		if (!$data)
			throw new Exception('Could not read stream');

		$this->db->query("UPDATE {$this->table} SET foto = '" . pg_escape_bytea($data) . "' WHERE id = " . $sticker->get('id'));
	}

	protected function _generate_query($conditions)
	{
		return "SELECT 
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
				l.privacy as toegevoegd_door__privacy
			FROM
				{$this->table} s
			LEFT JOIN leden l ON
				l.id = s.toegevoegd_door
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
				DEGREES(
					ACOS(
						COS(RADIANS(s.lat)) * COS(RADIANS(c.lat)) * COS(RADIANS(s.lng) - RADIANS(c.lng))
						+ SIN(RADIANS(s.lat)) * SIN(RADIANS(c.lat))
					)
				) * 111.045 as distance -- distance in KM
				FROM {$this->table} s
				RIGHT JOIN {$this->table} c ON c.id = %d
				LEFT JOIN leden l ON l.id = s.toegevoegd_door
				ORDER BY distance ASC
				LIMIT %d", $sticker->get('id'), $limit));

		return $this->_rows_to_iters($rows);
	}

	public function getRecentStickers($limit)
	{
		$rows = $this->find($this->_generate_query() . " ORDER BY s.toegevoegd_op DESC LIMIT " . intval($limit));

		return $this->_rows_to_iters($rows);
	}

	public function getRandomSticker()
	{
		$row = $this->db->query_first($this->_generate_query() . " ORDER BY RANDOM() DESC LIMIT 1");

		return $this->_row_to_iter($row);
	}

	public function memberCanEditSticker($sticker)
	{
		return member_in_commissie(COMMISSIE_BESTUUR) || $sticker->get('toegevoegd_door') == logged_in('id');
	}
}
