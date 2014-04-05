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

		$data = compact('label', 'omschrijving', 'lat', 'lng', 'toegevoegd_op');

		$iter = new DataIter($this->model, -1, $data);

		return $this->insert($iter, true);
	}

	public function getStickersInRange(GeoPoint $upperleft, GeoPoint $lowerright, $group = false)
	{

	}
}
