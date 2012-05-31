<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing bedrijven data
	  */
	class DataModelBedrijven extends DataModel {
		function DataModelBedrijven($db) {
			parent::DataModel($db, 'bedrijven_adres');
		}
		
		function get() {
			$rows = $this->db->query('SELECT * 
					FROM bedrijven_adres
					ORDER BY naam');
			
			return $this->_rows_to_iters($rows);
		}
		
		function delete($iter) {
			$result = parent::delete($iter);
			
			/* Also delete all stageplaatsen for this company */
			$this->db->delete('bedrijven_stageplaatsen',
					'bedrijf = ' . $iter->get_id());
			
			return $result;
		}
		
		function insert_afstudeerplaats($iter) {
			return $this->db->insert('bedrijven_stageplaatsen', $iter->data, 
					$iter->get_literals());
		}
		
		function delete_afstudeerplaats($iter) {
			return $this->db->delete('bedrijven_stageplaatsen', $this->_id_string($iter->get_id()));
					
		}
		
		function update_afstudeerplaats($iter) {
			return $this->db->update('bedrijven_stageplaatsen', 
					$iter->get_changed_values(), 
					$this->_id_string($iter->get_id()), 
					$iter->get_literals());
		}
		
		function get_afstudeerplaatsen($id) {
			$rows = $this->db->query('SELECT *
					FROM bedrijven_stageplaatsen
					WHERE bedrijf = ' . intval($id));
			
			return $this->_rows_to_iters($rows);
		}
		
		function get_afstudeerplaats($id) {
			$row = $this->db->query_first('SELECT *
					FROM bedrijven_stageplaatsen
					WHERE id = ' . intval($id));
			
			return $this->_row_to_iter($row);
		}
	}
?>
