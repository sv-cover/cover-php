<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing active member data
	  */
	class DataModelActieveLeden extends DataModel
	{
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
				GROUP BY l.id, l.voornaam, l.tussenvoegsel, l.achternaam, l.email, l.privacy
				ORDER BY voornaam, tussenvoegsel, achternaam ASC');

			return $this->_rows_to_iters($rows);
		}
	}
?>
