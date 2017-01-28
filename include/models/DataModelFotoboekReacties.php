<?php
require_once 'include/data/DataModel.php';

class DataIterReactie extends DataIter
{
	public function get_liked_by()
	{
		return get_model('DataModelMember')->find(sprintf('id IN (SELECT lid_id FROM foto_reacties_likes WHERE reactie_id = %d)', $this->id));
	}

	public function like(DataIterMember $member)
	{
		$this->model->db->insert('foto_reacties_likes', [
			'reactie_id' => $this->get_id(),
			'lid_id' => $member->get_id()
		]);

		// Just assume we removed a like, and remove it from the tally
		$this->data['likes']++;
	}

	public function unlike(DataIterMember $member)
	{
		$this->model->db->delete('foto_reacties_likes',
			sprintf('reactie_id = %d AND lid_id = %d',
				$this->get_id(),
				$member->get_id()));

		// Again, lets just assume :)
		$this->data['likes']--;
	}

	public function is_liked_by(DataIterMember $member)
	{
		return $this->model->db->query_value(sprintf(
			'SELECT COUNT(id) FROM foto_reacties_likes WHERE reactie_id = %d AND lid_id = %d',
			$this->get_id(), $member->get_id())) > 0;
	}

	public function get_likes()
	{
		return (int) $this->model->db->query_value(sprintf(
			'SELECT COUNT(id) FROM foto_reacties_likes WHERE reactie_id = %d',
			$this->get_id()));
	}
} 

class DataModelFotoboekReacties extends DataModel
{
	public $dataiter = 'DataIterReactie';

	public function __construct($db)
	{
		parent::__construct($db, 'foto_reacties');
	}

	public function get_for_photo(DataIter $photo)
	{
		return $this->find(sprintf('foto_reacties.foto = %d', $photo->get('id')));
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
					foto_boeken.titel AS fotoboek__titel,
					COUNT(f_r_l.id) as likes
				FROM 
					(SELECT * FROM foto_reacties ORDER BY date DESC LIMIT 10) as f_r
				LEFT JOIN foto_reacties_likes f_r_l ON
					f_r_l.reactie_id = f_r.id
				LEFT JOIN leden l ON
					f_r.auteur = l.id
				LEFT JOIN fotos ON
					fotos.id = f_r.foto
				LEFT JOIN foto_boeken ON
					foto_boeken.id = fotos.boek
				WHERE
					fotos.hidden = 'f'
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
			foto_reacties.id,
			foto_reacties.foto,
			foto_reacties.auteur,
			foto_reacties.reactie,
			foto_reacties.date,
			DATE_PART('dow', foto_reacties.date) AS dagnaam, 
			DATE_PART('day', foto_reacties.date) AS datum, 
			DATE_PART('month', foto_reacties.date) AS maand, 
			DATE_PART('hours', foto_reacties.date) AS uur, 
			DATE_PART('minutes', foto_reacties.date) AS minuut,
			(SELECT COUNT(f_r_l.id) FROM foto_reacties_likes f_r_l WHERE f_r_l.reactie_id = foto_reacties.id) as likes
			FROM {$this->table}
			" . ($where ? " WHERE {$where}" : "") . "
			ORDER BY date ASC";
	}
}
