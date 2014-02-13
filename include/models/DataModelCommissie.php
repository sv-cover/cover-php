<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing the Commissie data
	  */
	class DataModelCommissie extends DataModel {
		function DataModelCommissie($db) {
			parent::DataModel($db, 'commissies');
		}
		
		/**
		  * Get all commissies (optionally leaving out bestuur)
		  * @include_bestuur optional; whether or not to include
		  * bestuur
		  *
		  * @result an array of #DataIter
		  */
		function get($include_bestuur = true) {
			$rows = $this->db->query('SELECT * FROM commissies ' .
					(!$include_bestuur ? ' WHERE id != ' . COMMISSIE_BESTUUR : '') . 
					' ORDER BY naam');
			
			return $this->_rows_to_iters($rows);
		}
		
		function get_functies() {
			static $functies = Array('voorzitter' => 3, 'secretaris' => 2, 'penningmeester' => 1, 'algemeen lid' => 0);
			
			return $functies;
		}
		
		function _sort_leden($a, $b) {
			$functies = $this->get_functies();
			
			$afunctie = strtolower($a->get('functie'));
			$bfunctie = strtolower($b->get('functie'));
			
			if ($functies[$afunctie] == $functies[$bfunctie])
				return 0;
			
			return ($functies[$afunctie] < $functies[$bfunctie]) ? 1 : -1;
		}
		
		/**
		  * Get all members of a specific commissie
		  * @id the commissie id
		  *
		  * @result an array of #DataIter
		  */
		function get_leden($id) {
			$rows = $this->db->query('SELECT leden.id, 
					leden.voornaam, 
					leden.tussenvoegsel, 
					leden.achternaam, 
					leden.email, 
					leden.privacy,
					actieveleden.functie,
					actieveleden.sleutel,
					actieveleden.id AS actiefid
					FROM actieveleden, leden
					WHERE leden.id = actieveleden.lidid AND 
					actieveleden.commissieid = ' . intval($id));
			
			$iters = $this->_rows_to_iters($rows);
			
			/* Sort by function */
			usort($iters, array(&$this, '_sort_leden'));

			return $iters;
		}

		function get_commissies_for_member($lid_id)
		{
			$rows = $this->db->query("
				SELECT
					c.id,
					c.naam,
					c.page,
					al.functie
				FROM
					actieveleden al
				RIGHT JOIN commissies c ON
					al.commissieid = c.id
				WHERE
					al.lidid = " . intval($lid_id) ."
				GROUP by
					c.id,
					c.naam,
					c.page,
					al.functie
				ORDER BY
					c.naam ASC");

			return $this->_rows_to_iters($rows);
		}

		/**
		  * Get the login name of a specific commissie
		  * @id the commissie id
		  *
		  * @result the login name
		  */
		function get_login($id) {
			$value = $this->db->query_value('SELECT login 
					FROM commissies 
					WHERE id = ' . intval($id));
			
			if (!$value)
				return '';
			else
				return $value;
		}

		/**
		  * Get the email address of a commissie (composed of the
		  * login name (see #DataModelCommissie::get_login))
		  * @id the commissie id
		  *
		  * @result the commissie email address
		  */
		function get_email($id) {
			$value = $this->get_login($id);
					
			if (!$value)
				$value = __('onbekend');
			
			return $value . '@svcover.nl';
		}
		
		/**
		  * Get commissie name 
		  * @id the commissie id 
		  *
		  * @result the commissie name
		  */
		function get_naam($id) {
			$value = $this->db->query_value('SELECT naam 
					FROM commissies 
					WHERE id = ' . intval($id));
			
			if (!$value)
				return '';
			else
				return $value;
		}
		
		/**
		  * Get commissie page id
		  * @id the commissie id
		  *
		  * @result the commissie page id
		  */
		function get_page($id) {
			return $this->db->query_value('SELECT page 
					FROM commissies 
					WHERE id = ' . intval($id));
		}
		
		/**
		  * Gets a commissie from name
		  * @name the commissie name
		  *
		  * @result a #DataIter or null if not found
		  */
		function get_from_name($name) {
			$row = $this->db->query_first("SELECT * 
					FROM commissies
					WHERE naam = '" . $this->escape_string($name) . "'");
			
			return $this->_row_to_iter($row);
		}
		
		function delete($iter) {
			parent::delete($iter);
			
			/* Remove forum permissions */
			$forum_model = get_model('DataModelForum');
			$forum_model->commissie_deleted($iter);
		}
	}
?>
