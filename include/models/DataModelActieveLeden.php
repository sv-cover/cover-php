<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing active member data
	  */
	class DataModelActieveLeden extends DataModel {
		function DataModelActieveLeden($db) {
			parent::DataModel($db, 'actieveleden');
		}
		
		function search_members($query) {
			if (strlen($query) < 3)
				return array();

			$rows = $this->db->query('SELECT id,
					voornaam,
					tussenvoegsel,
					achternaam,
					privacy,
					FROM leden
					WHERE (voornaam ILIKE \'%' . $this->escape_string($query) . '%\' OR
					achternaam ILIKE \'%' . $this->escape_string($query) . '%\')
					AND type = 1
					ORDER BY voornaam, tussenvoegsel, achternaam');

			return $this->_rows_to_iters($rows);
		}
		
		function delete_by_commissie($id) {
			return $this->db->delete('actieveleden', 'commissieid = ' . intval($id));
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
