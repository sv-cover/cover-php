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

	public function getNearbyStickers($sticker_id, $limit)
	{
		$rows = $this->db->query(sprintf("SELECT s.*,
				DEGREES(
					ACOS(
						COS(RADIANS(s.lat)) * COS(RADIANS(c.lat)) * COS(RADIANS(s.lng) - RADIANS(c.lng))
						+ SIN(RADIANS(s.lat)) * SIN(RADIANS(c.lat))
					)
				) * 111.045 as distance -- distance in KM
				FROM {$this->table} s
				RIGHT JOIN {$this->table} c ON c.id = %d
				ORDER BY distance ASC
				LIMIT %d", $sticker_id, $limit);

		return $this->_rows_to_iters($rows);
	}

	public function getRecentStickers($limit)
	{
		$rows = $this->db->query("SELECT * FROM {$this->table} ORDER BY toegevoegd_op DESC LIMIT {$limit}");

		return $this->_rows_to_iters($rows);
	}

	public function getStickersInRange(GeoPoint $upperleft, GeoPoint $lowerright, $group = false)
	{

	}
}
