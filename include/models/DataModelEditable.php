<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing the Editable data
	  */
	class DataModelEditable extends DataModel {
		function DataModelEditable($db) {
			parent::DataModel($db, 'pages');
		}
		
		/**
		  * Gets an editable page from a title
		  * @title the title of the editable page
		  * 
		  * @result a #DataIter or null of no such page could be
		  * found
		  */
		function get_iter_from_title($title) {
			return $this->_row_to_iter($this->db->query_first("SELECT * 
					FROM pages
					WHERE titel = '" . $this->escape_string($title) . "'"));
		}
	}
?>
