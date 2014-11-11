<?php
	require_once('data/DataModel.php');

	/**
	  * A class implementing the Commissie data
	  */
	class DataModelCommissie extends DataModel
	{
		public function __construct($db)
		{
			parent::__construct($db, 'commissies');
		}

		protected function _generate_query($conditions)
		{
			return parent::_generate_query($conditions) . ' ORDER BY naam ASC';
		}
		
		/**
		  * Get all commissies (optionally leaving out bestuur)
		  * @include_bestuur optional; whether or not to include
		  * bestuur
		  *
		  * @result an array of #DataIter
		  */
		public function get($include_hidden = true)
		{
			return $this->find(!$include_hidden ? 'hidden <> 1' : '');
		}

		public function insert(DataIter $iter, $getid = false)
		{
			if ($iter->has('vacancies') && !$iter->get('vacancies'))
				$iter->set_literal('vacancies', 'NULL');
			
			return parent::insert($iter, $getid);
		}

		public function update(DataIter $iter)
		{
			if ($iter->has('vacancies') && !$iter->get('vacancies'))
				$iter->set_literal('vacancies', 'NULL');
			
			return parent::update($iter);
		}
		
		public function get_functies()
		{
			static $functies = Array(
				'voorzitter' => 5,
				'secretaris' => 4,
				'penningmeester' => 3,
				'commissaris intern' => 2,
				'commissaris extern' => 1,
				'algemeen lid' => 0);
			
			return $functies;
		}

		protected function _get_functie($functie)
		{
			$functies = $this->get_functies();
			$functie = strtolower($functie);
			return isset($functies[$functie]) ? $functies[$functie] : 0;
		}
		
		protected function _sort_leden($a, $b)
		{
			$pattern = '/\s*[,\/]\s*/';

			$afunctie = max(array_map(array($this, '_get_functie'), preg_split($pattern, $a->get('functie'))));
			$bfunctie = max(array_map(array($this, '_get_functie'), preg_split($pattern, $b->get('functie'))));
			
			return $afunctie == $bfunctie ? 0 : $afunctie < $bfunctie ? 1 : -1;
		}
		
		/**
		  * Get all members of a specific commissie
		  * @id the commissie id
		  *
		  * @result an array of #DataIter
		  */
		public function get_leden($id)
		{
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

		public function get_lid_for_functie($commissie_id, $functie)
		{
			$leden = $this->get_leden($commissie_id);

			foreach ($leden as $lid)
				if (strcasecmp($lid->get('functie'), $functie) === 0)
					return $lid;

			return null;
		}

		public function get_commissies_for_member($lid_id)
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
		public function get_login($id) {
			return $this->db->query_value('SELECT login 
					FROM commissies 
					WHERE id = ' . intval($id));
		}

		public function get_from_email($email)
		{
			if (substr($email, -11) == '@svcover.nl')
				$email = substr($email, 0, -11);

			$row = $this->db->query_first("SELECT * FROM commissies WHERE login = '" . $this->db->escape_string(strtolower($email)) . "'");

			return $this->_row_to_iter($row);
		}

		/**
		  * Get the email address of a commissie (composed of the
		  * login name (see #DataModelCommissie::get_login))
		  * @id the commissie id
		  *
		  * @result the commissie email address
		  */
		public function get_email($id)
		{
			$value = $this->get_login($id);
					
			if (!$value)
				$value = __('onbekend');
			
			return strstr($value, '@') ? $value : $value . '@svcover.nl';
		}
		
		/**
		  * Get commissie name 
		  * @id the commissie id 
		  *
		  * @result the commissie name
		  */
		public function get_naam($id)
		{
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
		public function get_page($id)
		{
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
		public function get_from_name($name)
		{
			$row = $this->db->query_first("SELECT * 
					FROM commissies
					WHERE '" . $this->db->escape_string($name) . "' IN (naam, login, nocaps)");
			
			return $this->_row_to_iter($row);
		}

		public function get_random()
		{
			$row = $this->db->query_first("SELECT c.* 
					FROM commissies c
					LEFT JOIN actieveleden a ON
						a.commissieid = c.id
					WHERE c.hidden <> 1
					GROUP BY c.id
					HAVING COUNT(a.id) > 0
					ORDER BY RANDOM()
					LIMIT 1");
					
			return $this->_row_to_iter($row);
		}

		public function get_from_page($page_id)
		{
			$row = $this->db->query_first(sprintf("SELECT * 
					FROM commissies
					WHERE page = %d", $page_id));
			
			return $this->_row_to_iter($row);
		}
		
		public function delete(DataIter $iter)
		{
			parent::delete($iter);
			
			/* Remove forum permissions */
			$forum_model = get_model('DataModelForum');
			$forum_model->commissie_deleted($iter);
		}
	}
?>
