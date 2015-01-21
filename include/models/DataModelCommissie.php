<?php
	require_once 'include/search.php';
	require_once 'include/data/DataModel.php';
	require_once 'include/models/DataModelMember.php';
	
	class DataIterCommissie extends DataIter implements SearchResult
	{
		public function get_members()
		{
			return $this->model->get_members($this);
		}

		public function set_members(array $members)
		{
			return $this->model->set_members($this, $members);
		}

		public function get_summary()
		{
			/* Get the first editable page */
			$editable_model = get_model('DataModelEditable');

			return $editable_model->get_summary($this->get('page'));
		}

		public function get_search_relevance()
		{
			return floatval($this->get('search_relevance'));
		}

		public function get_search_type()
		{
			return 'committee';
		}
	}

	/**
	  * A class implementing the Commissie data
	  */
	class DataModelCommissie extends DataModel implements SearchProvider
	{
		public $dataiter = 'DataIterCommissie';

		public $fields = array(
			'id',
			'naam',
			'login',
			'website',
			'nocaps',
			'page',
			'hidden',
			'vacancies');

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

		public function insert(DataIter $iter)
		{
			if ($iter->has('vacancies') && !$iter->get('vacancies'))
				$iter->set_literal('vacancies', 'NULL');

			$iter->set('nocaps', strtolower($iter->get('naam')));
			
			$committee_id = parent::insert($iter);

			// Create the page for this committee
			$editable_model = get_model('DataModelEditable');

			$page_data = array(
				'owner' => $committee_id,
				'titel' => $iter->get('naam'));

			$page = new DataIter($editable_model, -1, $page_data);

			$page_id = $editable_model->insert($page);

			$this->db->update($this->table, array('page' => $page_id), $this->_id_string($committee_id), array());

			return $committee_id;
		}

		public function update(DataIter $iter)
		{
			if ($iter->has('vacancies') && !$iter->get('vacancies'))
				$iter->set_literal('vacancies', 'NULL');
			
			return parent::update($iter);
		}

		public function delete(DataIter $iter)
		{
			// Remove committee page
			$editable_model = get_model('DataModelEditable');

			try {
				$page = $editable_model->get_iter($iter->get('page'));
				$editable_model->delete($page);
			} catch (DataIterNotFoundException $e) {
				// Well, never mind.
			}

			// Remove members from committee
			$this->set_members($iter, array());

			// Remove forum permissions
			$forum_model = get_model('DataModelForum');
			$forum_model->commissie_deleted($iter);

			

			return parent::delete($iter);
		}
		
		public function get_functies()
		{
			static $functies = array(
				'Voorzitter' => 5,
				'Secretaris' => 4,
				'Penningmeester' => 3,
				'Commissaris Intern' => 2,
				'Commissaris Extern' => 1,
				'Algemeen Lid' => 0);
			
			return $functies;
		}

		protected function _get_functie($functie)
		{
			$functies = array_combine(
				array_map('strtolower', array_keys($this->get_functies())),
				array_values($this->get_functies()));

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
		public function get_members(DataIterCommissie $committee)
		{
			$rows = $this->db->query('SELECT
					leden.id, 
					leden.voornaam, 
					leden.tussenvoegsel, 
					leden.achternaam, 
					leden.email, 
					leden.privacy,
					actieveleden.functie
					FROM actieveleden, leden
					WHERE leden.id = actieveleden.lidid AND 
					actieveleden.commissieid = ' . $committee->get_id());
			
			$iters = $this->_rows_to_iters($rows, 'DataIterMember');
			
			/* Sort by function */
			usort($iters, array(&$this, '_sort_leden'));

			return $iters;
		}

		public function get_lid_for_functie($commissie_id, $functie)
		{
			$committee = $this->get_iter($commissie_id);

			$leden = $this->get_members($committee);

			foreach ($leden as $lid)
				if (strcasecmp($lid->get('functie'), $functie) === 0)
					return $lid;

			return null;
		}

		public function set_members(DataIterCommissie $committee, array $members)
		{
			$this->db->delete('actieveleden', sprintf('commissieid = %d', $committee->get_id()));

			foreach ($members as $member_id => $position)
				$this->db->insert('actieveleden', array(
					'commissieid' => $committee->get_id(),
					'lidid' => intval($member_id),
					'functie' => $position));
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

		public function search($query, $limit = null)
		{
			$query = sprintf("
				SELECT
					c.*,
					1 as search_relevance
				FROM
					commissies c
				WHERE
					c.naam ILIKE '%%%s%%'
				UNION
				SELECT
					c.*,
					-1 as search_relevance
				FROM
					actieveleden al
				INNER JOIN leden l ON
					l.id = al.lidid
				INNER JOIN commissies c ON
					c.id = al.commissieid
				WHERE
					c.hidden <> 1
					AND (CASE
						WHEN coalesce(tussenvoegsel, '') = '' THEN
							voornaam || ' ' || achternaam
						ELSE
							voornaam || ' ' || tussenvoegsel || ' ' || achternaam
					END ILIKE '%%%1\$s%%')",
				$this->db->escape_string($query));

			return $this->_rows_to_iters($this->db->query($query));
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
	}
