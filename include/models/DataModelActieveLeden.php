<?php
	require_once 'include/data/DataModel.php';

	class DataIterMembership extends DataIter
	{
		//
	}

	/**
	  * A class implementing active member data
	  */
	class DataModelActieveLeden extends DataModel
	{
		public $fields = array(
			'id',
			'lidid',
			'commissieid',
			'functie',
			'started_on',
			'discharged_on'
		);

		public $dataiter = 'DataIterMembership';

		public function __construct($db)
		{
			parent::__construct($db, 'actieveleden');
		}
		
		function get_active_members()
		{
			$rows = $this->db->query('SELECT DISTINCT
				l.id,
				l.voornaam,
				l.tussenvoegsel,
				l.achternaam,
				l.email,
				l.privacy,
				COUNT(a.commissieid) as commissie_count
				FROM actieveleden a
				LEFT JOIN leden l ON a.lidid = l.id
				WHERE a.discharged_on IS NULL
				GROUP BY l.id, l.voornaam, l.tussenvoegsel, l.achternaam, l.email, l.privacy
				ORDER BY voornaam, tussenvoegsel, achternaam ASC');

			return $this->_rows_to_iters($rows);
		}

		protected function _generate_query($where)
		{
			if ($where)
				$where = "WHERE {$where}";
			
			return "SELECT
					m.id,
					m.lidid,
					m.commissieid,
					m.functie,
					m.started_on,
					m.discharged_on,
					l.id lid__id,
					l.voornaam lid__voornaam,
					l.tussenvoegsel lid__tussenvoegsel,
					l.achternaam lid__achternaam,
					l.email lid__email,
					l.privacy lid__privacy,
					c.id commissie__id,
					c.naam commissie__naam,
					c.login commissie__login,
					c.website commissie__website,
					c.nocaps commissie__nocaps,
					c.page commissie__page,
					c.hidden commissie__hidden,
					c.vacancies commissie__vacancies
				FROM
					{$this->table} m
				INNER JOIN leden l ON
					l.id = m.lidid
				INNER JOIN commissies c ON
					c.id = m.commissieid
				{$WHERE}
				GROUP BY
					m.id,
					l.id,
					c.id
				ORDER BY
					m.id DESC";
		}
	}
