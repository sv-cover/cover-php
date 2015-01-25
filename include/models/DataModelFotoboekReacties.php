<?php
require_once 'include/data/DataModel.php';

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
					f_r.*,
					DATE_PART('dow', f_r.date) AS dagnaam, 
					DATE_PART('day', f_r.date) AS datum, 
					DATE_PART('month', f_r.date) AS maand, 
					DATE_PART('hours', f_r.date) AS uur, 
					DATE_PART('minutes', f_r.date) AS minuut,
					fotos.beschrijving,
					fotos.boek,
					foto_boeken.titel
				FROM 
					(SELECT * FROM foto_reacties ORDER BY date DESC LIMIT 10) as f_r
				LEFT JOIN fotos ON
					fotos.id = f_r.foto
				LEFT JOIN foto_boeken ON
					foto_boeken.id = fotos.boek
				GROUP BY
					f_r.id,
					f_r.foto,
					f_r.auteur,
					f_r.reactie,
					f_r.date,
					fotos.beschrijving,
					fotos.boek,
					foto_boeken.titel
				ORDER BY
					f_r.date DESC
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
