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
					achternaam
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
	}
?>
