<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing news data
	  */
	class DataModelNews extends DataModel {
		function DataModelNews($db) {
			parent::DataModel($db, 'news');
		}
		
		function get_last($num) {
			$rows = $this->db->query("SELECT *, 
					DATE_PART('dow',date) as dagnaam, 
					DATE_PART('day',date) AS datum, 
					DATE_PART('month',date) AS maand, 
					DATE_PART('hours',date) AS uur, 
					DATE_PART('minutes',date) AS minuut 
					FROM news 
					ORDER BY id DESC LIMIT " . intval($num));
			
			return $this->_rows_to_iters($rows);
		}
	}
?>
