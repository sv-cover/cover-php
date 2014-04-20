<?php
	require_once('data/DataModel.php');
	require_once('login.php');

	define('MEMBER_STATUS_LID', 1);
	define('MEMBER_STATUS_LID_ONZICHTBAAR', 4);
	define('MEMBER_STATUS_LID_AF', 2);
	define('MEMBER_STATUS_ERELID', 3);
	define('MEMBER_STATUS_DONATEUR', 5);

	/**
	  * A class implementing the Member data
	  */
	class DataModelMember extends DataModel
	{
		public $visible_types = array(
			MEMBER_STATUS_LID,
			MEMBER_STATUS_ERELID,
			MEMBER_STATUS_DONATEUR
		);

		function DataModelMember($db) {
			parent::DataModel($db, 'leden');
		}
		
		function _generate_select() {
			return 'leden.*, 
				profielen.lidid,
				profielen.wachtwoord,
				profielen.onderschrift,
				profielen.avatar,
				profielen.homepage,
				profielen.msn,
				profielen.icq,
				profielen.nick,
				profielen.taal';
		}

		public function get_all()
		{
			$rows = $this->db->query("
				SELECT
					*
				FROM
					leden
				WHERE
					type IN (" . implode(',', $this->visible_types) . ")
				ORDER BY
					leden.achternaam ASC,
					leden.voornaam ASC");

			return $this->_rows_to_iters($rows);
		}
		
		function get_iter($id) {
			$row = $this->db->query_first('SELECT ' . $this->_generate_select() . ' 
					FROM leden, profielen 
					WHERE leden.id = profielen.lidid AND leden.id = ' . intval($id));
			
			return $this->_row_to_iter($row);
		}
		
		function get_jarigen() {
			$rows = $this->db->query('
					SELECT
						id,
						voornaam,
						tussenvoegsel,
						achternaam,
						privacy,
						(EXTRACT(YEAR FROM CURRENT_TIMESTAMP) - EXTRACT(YEAR FROM geboortedatum)) AS leeftijd
					FROM
						leden
					WHERE 
						EXTRACT(MONTH FROM geboortedatum) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP) AND
						EXTRACT(DAY FROM geboortedatum) = EXTRACT(DAY FROM CURRENT_TIMESTAMP) AND 
						type IN (' . implode(',', $this->visible_types) . ') AND
						geboortedatum <> \'1970-01-01\'
					ORDER BY
						voornaam, tussenvoegsel, achternaam');
			
			return $this->_rows_to_iters($rows);					
		}
    
		/**
		  * Returns the photo of member with given id as a string.
		  * Still needs to be unescaped with pg_unescape_bytea()
		  * @iter a #DataIter
		  *
		  * @result the raw picture data
		  */
		public function get_photo($iter) {
			$photo = $this->db->query_first('SELECT foto from lid_fotos WHERE lid_id = ' . $this->get_photo_id($iter) . ' ORDER BY id DESC LIMIT 1');

			return $photo ? pg_unescape_bytea($photo['foto']) : null;
		}

		public function get_photo_mtime($iter)
		{
			$row = $this->db->query_first('SELECT EXTRACT(EPOCH FROM foto_mtime) as mtime FROM lid_fotos WHERE lid_id = ' . $this->get_photo_id($iter) . ' ORDER BY id DESC LIMIT 1');

			return (int) $row['mtime'] - 7200; // timezone difference?
 		}

		public function set_photo($iter, $fh)
		{
			$this->db->query(sprintf("INSERT INTO lid_fotos (lid_id, foto, foto_mtime) VALUES (%d, '%s', NOW())",
				$iter->get('id'), pg_escape_bytea(stream_get_contents($fh))));
		}

		/** 
		  * Returns the id of the photo. For members without a picture, 
		  * returns -1. For members with private pictures, returns -2.
		  * @iter a #DataIter
		  *
		  * @result the id of the photo of the member
		  */
		function get_photo_id($iter) {
			if (!$this->has_picture($iter)) 
				return -1;
			if ($this->is_private($iter,"foto",true))
				return -2;
			return $iter->get_id();
		}
		
		
		/**
		  * Returns true if the member has a picture
		  * @iter a #DataIter
		  *
		  * @result true if member has a picture
		  */
		function has_picture($iter) {
			if ($this->db->query_first('SELECT id from lid_fotos WHERE lid_id = ' . $iter->get_id()))
				return true;

			return false;
		}

		function get() {
			$rows = $this->db->query('SELECT ' . $this->_generate_select() . ' 
					FROM leden, profielen 
					WHERE leden.id = profielen.lidid');
			
			return $this->_rows_to_iters($rows);
		}

		/** 
		  * Get member data from email and password combination. This
		  * function will also add an array with commissie ids to
		  * the member data if a member can be found with that
		  * email and password combination. The password will
		  * be md5'd by the function
		  * @email the email of the member
		  * @passwd the password of the member
		  *
		  * @result an associative array with the member data or
		  * false if no member could be found
		  */
		function login($email, $passwd) {
			$row = $this->db->query_first("SELECT 
					leden.id, 
					leden.type
					FROM leden, profielen 
					WHERE leden.id = profielen.lidid AND 
					leden.email = '" . $this->escape_string($email) . "' AND 
					profielen.wachtwoord = '" . $this->escape_string($passwd) . "'");

			$active_member_types = array(
				MEMBER_STATUS_LID,
				MEMBER_STATUS_LID_ONZICHTBAAR,
				MEMBER_STATUS_ERELID,
				MEMBER_STATUS_DONATEUR);

			if (!$row || !in_array($row['type'], $active_member_types))
				return false;

			return $this->_row_to_iter($row);
		}
		
		/**
		  * Get commissies a certain member is in
		  * @memberid the id of the member
		  *
		  * @result an array of commissie ids
		  */
		function get_commissies($memberid) {
			$rows = $this->db->query("SELECT commissieid
					FROM actieveleden 
					WHERE lidid = " . intval($memberid));
			
			$commissies = array();

			if (!$rows)
				return $commissies;
			
			foreach ($rows as $row)
				$commissies[] = $row['commissieid'];
			
			return $commissies;
		}
		
		/** 
		  * Get a member from an email address
		  * @email the email address of the member
		  *
		  * @result a #DataIter or null of there is no member with
		  * such an email address
		  */
		function get_from_email($email) {
			$row = $this->db->query_first("SELECT *
					FROM leden
					WHERE leden.email = '" . $this->escape_string($email) . "'");
			
			return $this->_row_to_iter($row);
		}
		
		/** 
		  * Update a member profiel
		  * @iter a #DataIter with the profiel data
		  *
		  * @result true if the update was successful, false otherwise 
		  */
		function update_profiel($iter) {
			return $this->db->update('profielen', 
					$iter->get_changed_values(), 'lidid = ' . $iter->get_id(), 
					$iter->get_literals());
		}
		
		/**
		  * Get the full name from a iter
		  * @iter a #DataIter
		  *
		  * @result the members full name
		  */
		function get_full_name($iter) {
			return $iter->get('voornaam') . ($iter->get('tussenvoegsel') ? (' ' . $iter->get('tussenvoegsel')) : '') . ' ' . $iter->get('achternaam');
		}

		/**
		  * Get all the privacy fields
		  *
		  * @result an array of privacy_field_name => privacy_field_id
		  */
		function get_privacy() {
			$rows = $this->db->query('SELECT * FROM profielen_privacy');
			
			$privacy = array();
			
			if (!$rows)
				return $privacy;
			
			foreach ($rows as $row)
				$privacy[$row['field']] = intval($row['id']);
			
			return $privacy;
		}
		
		/**
		  * Returns whether a given field in iter is private or not.
		  * This function determines the privacy state of the field and
		  * checks if it matches with the currently logged in member
		  * @iter a #DataIter containing a privacy field containing 
		  * the privacy bitmask information
		  * @field the name of the field to check the privacy for
		  *	@field if true, always return true if requested iter
		  * is the currently logged in member.
		  * @result true if the field is private, false otherwise
		  */
		function is_private($iter, $field, $self=false) {
			$value = $this->get_privacy_for_field($iter,$field);
			$cur = logged_in();
			if ($cur && $self && $cur['id'] == $iter->get_id()) {
				return false;
			}
			if ($value == 0) /* Visible to none */
				return true;
			elseif ($value == 7) /* Visible to all */
				return false;
			elseif (($value & 1) && !logged_in_as_active_member()) /* Visible to members */
				return true;
			else
				return false;
		}

		public function is_visible($iter)
		{
			return in_array($iter->get('type'), $this->visible_types);
		}
		
		/**
		  * Return the privacy value for a field
		  * @result integer that corresponds to privacy
		  */
		
		function get_privacy_for_field($iter,$field) {
			static $privacy = null;
			
			if ($privacy == null)
				$privacy = $this->get_privacy();
	
			if (!array_key_exists($field, $privacy))
				return false;
			
			$value = ($iter->get('privacy') >> ($privacy[$field] * 3)) & 7;
			return $value;
		}
		
		/*
		 * Returns true if field is viewable for all
		 *
		 */
		function privacy_public_for_field($iter,$field) {
			$value = $this->get_privacy_for_field($iter,$field);
			return ($value == 7);
		}
		
		/**
		  * Get members by searching in their first and last names.
		  * Only a part of the name needs to be matched.
		  * @first a part of the first name to search for
		  * @last a part of the last name to search for
		  *
		  * @result an array of #DataIter
		  */
		function get_from_search_first_last($first, $last) {
			
			$query = 'SELECT l.*, s.studie
				FROM leden l
				LEFT JOIN studies s ON s.lidid = l.id
				WHERE l.type IN (' . implode(',', $this->visible_types) . ') ';
			
			$order = array();
			
			if ($first) {
				$query .= " AND l.voornaam ILIKE '%" . $this->escape_string($first) . "%'";
				$order[] = 'l.voornaam';
			}
			
			if ($last) {
				$query .= " AND l.achternaam ILIKE '%" . $this->escape_string($last) . "%'";
				$order[] = 'l.achternaam';
			}

			if (count($order) > 0)
				$query .= ' ORDER BY ' . implode(', ', $order);
						
			$rows = $this->db->query($query);

			$rows = $this->_aggregate_rows($rows, array('studie'), 'id');

			return $this->_rows_to_iters($rows);
		}

		/**
		 * @author Jelmer van der Linde
		 * Group rows by $group_by_column and in the process turn all fields
		 * named in $aggregate_fields into arrays. This is a bit of a dirty
		 * (Ok, a really dirty replacement) for array_agg in Postgres.
		 *
		 * @rows the raw database rows
		 * @aggregate_fields fields that need to be collected for each group
		 * @group_by_column name of the column which identifies the group
		 * @result array of groupes
		 */
		protected function _aggregate_rows($rows, array $aggregate_fields, $group_by_column)
		{
			$grouped = array();

			foreach ($rows as $row)
			{
				$key = $row[$group_by_column];

				if (isset($grouped[$key]))
				{
					foreach ($aggregate_fields as $field)
						$grouped[$key][$field][] = $row[$field];
				}
				else
				{
					$grouped[$key] = $row;
					
					foreach ($aggregate_fields as $field)
						$grouped[$key][$field] = array($row[$field]);
				}
			}

			return array_values($grouped);
		}
		
		/** @author Pieter de Bie
		  * Get members by searching in their first OR last names.
		  * Only a part of the name needs to be matched.
		  * @first a part of the first name to search for
		  * @last a part of the last name to search for
		  *
		  * @result an array of #DataIter
		  */
		function search_first_last($name) {
			if (!$name) {
				return null;
			}

			$name = $this->escape_string($name);

			$query = "SELECT *
					FROM leden
					WHERE type IN (" . implode(',', $this->visible_types) . ")
					AND (voornaam ILIKE '%$name%' OR achternaam ILIKE '%$name%');";
					
			$rows = $this->db->query($query);			
			return $this->_rows_to_iters($rows);			
		}
		
		/**
		  * Get members by searching for their starting year
		  *
		  * @result an array of #DataIter
		  */
		function get_from_search_year($year) {
			$rows = $this->db->query("SELECT *
					FROM leden
					WHERE type IN (" . implode(',', $this->visible_types) . ")
					AND beginjaar = " . intval($year) . "
					ORDER BY achternaam");
			
			return $this->_rows_to_iters($rows);		
		}
		
		/** 
		  * Get all years that have active members
		  * 
		  * @result an array of active years
		  */
		function get_distinct_years() {
			$rows = $this->db->query("SELECT DISTINCT beginjaar
						FROM leden
						WHERE type IN (" . implode(',', $this->visible_types) . ")
						ORDER BY beginjaar ASC");
			$rows = $this->_rows_to_iters($rows);
			$years = array();
			foreach ($rows as $row) {
				array_push($years,$row->get('beginjaar'));
			}
			return $years;
		}
		

		/**
		  * Get members by the first character of their last name
		  * @char the first character of the last name
		  *
		  * @result an array of #DataIter
		  */
		function get_from_last_character($char) {
			$rows = $this->db->query("SELECT *
					FROM leden
					WHERE type IN (" . implode(',', $this->visible_types) . ")
					AND achternaam ILIKE '" . $this->escape_string($char) . "%'
					ORDER BY achternaam");
			
			return $this->_rows_to_iters($rows);		
		}

		/**
		  * Get members by the first character of their first name
		  * @char the first character of the first name
		  *
		  * @result an array of #DataIter
		  */
		function get_from_first_character($char) {
			$rows = $this->db->query("SELECT *
					FROM leden
					WHERE type IN (" . implode(',', $this->visible_types) . ")
					AND voornaam ILIKE '" . $this->escape_string($char) . "%' 
					ORDER BY voornaam");
			
			return $this->_rows_to_iters($rows);
		}

		function get_from_status($status)
		{
			$rows = $this->db->query("SELECT *
					FROM leden
					WHERE type =  " . intval($status)  . "
					ORDER BY voornaam");

			return $this->_rows_to_iters($rows);
		}
		
		function get_status($iter)
		{
			switch ($iter->get('type'))
			{
				case MEMBER_STATUS_LID:
					return __('Lid');

				case MEMBER_STATUS_LID_ONZICHTBAAR:
					return __('Onzichtbaar');

				case MEMBER_STATUS_LID_AF:
					return __('Lid af');

				case MEMBER_STATUS_ERELID:
					return __('Erelid');

				case MEMBER_STATUS_DONATEUR:
					return __('Donateur');

				default:
					return __('Onbekend');
			}
		}

		/**
		  * Insert a profiel
		  * @iter a #DataIter representing the profiel
		  *
		  * @result whether the insert was successful
		  */	
		function insert_profiel($iter) {
			return $this->_insert('profielen', $iter);
		}
		
		/**
		  * Check if a member already exists
		  * @memberid the member id
		  *
		  * @result true if the member id is already used, false
		  * otherwise
		  */
		function exists($memberid) {
			$val = $this->db->query_value('SELECT 1
					FROM leden
					WHERE id = ' . intval($memberid));
			
			return ($val == 1);
		}
	}
?>
