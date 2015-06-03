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
					l.id as auteur__id,
					l.voornaam as auteur__voornaam,
					l.tussenvoegsel as auteur__tussenvoegsel,
					l.achternaam as auteur__achternaam,
					l.privacy as auteur__privacy,
					DATE_PART('dow', f_r.date) AS dagnaam, 
					DATE_PART('day', f_r.date) AS datum, 
					DATE_PART('month', f_r.date) AS maand, 
					DATE_PART('hours', f_r.date) AS uur, 
					DATE_PART('minutes', f_r.date) AS minuut,
					fotos.beschrijving AS foto__beschrijving,
					fotos.id AS foto__id,
					fotos.boek AS foto__boek,
					fotos.width AS foto__width,
					fotos.height AS foto__height,
					foto_boeken.id AS fotoboek__id,
					foto_boeken.titel AS fotoboek__titel
				FROM 
					(SELECT * FROM foto_reacties ORDER BY date DESC LIMIT 10) as f_r
				LEFT JOIN leden l ON
					f_r.auteur = l.id
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
					l.id,
					l.voornaam,
					l.tussenvoegsel,
					l.achternaam,
					l.privacy,
					fotos.id,
					fotos.beschrijving,
					fotos.boek,
					foto_boeken.id,
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
