<?php
require_once 'data/DataModel.php';

class DataModelFotoboekReacties extends DataModel
{
	public function __construct($db)
	{
		parent::__construct($db, 'foto_reacties');
	}

	public function get_for_photo(DataIter $photo)
	{
		return $this->find(sprintf('foto = %d', $photo->get('id')));
	}

	public function get_latest($num)
	{
		$rows = $this->db->query("
				SELECT
					foto_reacties.*,
					DATE_PART('dow', foto_reacties.date) AS dagnaam, 
					DATE_PART('day', foto_reacties.date) AS datum, 
					DATE_PART('month', foto_reacties.date) AS maand, 
					DATE_PART('hours', foto_reacties.date) AS uur, 
					DATE_PART('minutes', foto_reacties.date) AS minuut,
					fotos.beschrijving,
					fotos.boek,
					foto_boeken.titel
				FROM 
					foto_reacties,
					fotos,
					foto_boeken
				WHERE
					fotos.id = foto_reacties.foto AND
					fotos.boek = foto_boeken.id
				ORDER BY
					date DESC
				LIMIT " . intval($num));

		return $this->_rows_to_iters($rows);
	}

	protected function _generate_query($where)
	{
		return "SELECT
			id,
			foto,
			auteur,
			reactie,
			date,
			DATE_PART('dow', date) AS dagnaam, 
			DATE_PART('day', date) AS datum, 
			DATE_PART('month', date) AS maand, 
			DATE_PART('hours', date) AS uur, 
			DATE_PART('minutes', date) AS minuut
			FROM {$this->table}"
			. ($where ? " WHERE {$where}" : "")
			. " ORDER BY date ASC";
	}
}
