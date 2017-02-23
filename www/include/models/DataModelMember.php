<?php
	require_once 'include/search.php';
	require_once 'include/login.php';
	require_once 'include/data/DataModel.php';

	class DataIterMember extends DataIter implements SearchResult
	{
		static public function fields()
		{
			return [
				'id',
				'voornaam',
				'tussenvoegsel',
				'achternaam',
				'adres',
				'postcode',
				'woonplaats',
				'email',
				'geboortedatum',
				'geslacht',
				'telefoonnummer',
				'privacy',
				'type',
				'machtiging',
				'beginjaar',
				'lidid',
				'onderschrift',
				'avatar',
				'homepage',
				'nick',
				'taal',
			];
		}

		public function get_naam()
		{
			trigger_error('Use DataIterMember::full_name instead of DataIterMember::naam', E_USER_NOTICE);
			
			return member_full_name($this);
		}

		public function get_full_Name()
		{
			return member_full_name($this);
		}

		public function is_private($field)
		{
			return $this->model->is_private($this, $field);
		}

		public function get_search_relevance()
		{
			return 0.5 + normalize_search_rank($this['number_of_committees']);
		}

		public function get_search_type()
		{
			return 'member';
		}

		public function get_absolute_url()
		{
			return sprintf('profiel.php?lid=%d', $this->get_id());
		}

		public function has_photo()
		{
			return $this->model->has_photo($this);
		}

		public function get_photo()
		{
			return $this->model->get_photo($this);
		}

		public function get_photo_mtime()
		{
			return $this->model->get_photo_mtime($this);
		}

		/**
		 * Note: this getter returns a list of committee id's, not actual DataIterCommittee[]
		 */
		public function get_committees()
		{
			return $this->model->get_commissies($this->get_id());
		}
	}

	class DataModelMember extends DataModel implements SearchProvider
	{
		const VISIBLE_TO_NONE = 0;
		const VISIBLE_TO_MEMBERS = 1;
		const VISIBLE_TO_EVERYONE = 7;

		public $visible_types = array(
			MEMBER_STATUS_LID,
			MEMBER_STATUS_ERELID,
			MEMBER_STATUS_DONATEUR
		);

		public $dataiter = 'DataIterMember';

		protected $auto_increment = false;

		public function __construct($db)
		{
			parent::__construct($db, 'leden');
		}

		public function get_jarigen()
		{
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

		public function has_photo(DataIterMember $iter)
		{
			return (bool) $this->db->query_first('SELECT id from lid_fotos WHERE lid_id = ' . $iter->get_id());
		}
		
		public function get_photo(DataIter $iter)
		{
			$photo = $this->db->query_first('SELECT foto from lid_fotos WHERE lid_id = ' . $iter->get_id() . ' ORDER BY id DESC LIMIT 1');

			return $photo ? $this->db->read_blob($photo['foto']) : null;
		}

		public function get_photo_mtime(DataIter $iter)
		{
			$row = $this->db->query_first('SELECT EXTRACT(EPOCH FROM foto_mtime) as mtime FROM lid_fotos WHERE lid_id = ' . $iter->get_id() . ' ORDER BY id DESC LIMIT 1');

			return (int) $row['mtime'] - 7200; // timezone difference?
 		}

		public function set_photo(DataIter $iter, $fh)
		{
			$this->db->query(sprintf("INSERT INTO lid_fotos (lid_id, foto, foto_mtime) VALUES (%d, '%s', NOW())",
				$iter->get_id(), $this->db->write_blob($fh)));
		}

		/**
		  * Returns true if the member has a picture
		  * @iter a #DataIter
		  *
		  * @result true if member has a picture
		  */
		public function has_picture(DataIter $iter)
		{
			return $this->has_photo($iter);
		}

		/**
		 * Get limited member data (id, type, wachtwoord) from email and password combination.
		 * @param $email string email address of the user
		 * @param $passwd string password of the user
		 * @return bool|DataIterMember
		 */
		public function login($email, $passwd)
		{
			$iter = $this->get_from_email($email);
			
			$active_member_types = array(
				MEMBER_STATUS_LID,
				MEMBER_STATUS_LID_ONZICHTBAAR,
				MEMBER_STATUS_ERELID,
				MEMBER_STATUS_DONATEUR,
				MEMBER_STATUS_UNCONFIRMED);

			if ($iter === null || !in_array($iter['type'], $active_member_types))
				return false;

			if (!$this->test_password($iter, $passwd))
				return false;

			return $iter;
		}

		public function test_password(DataIterMember $member, $password)
		{
			$stored_password = $this->db->query_value(sprintf('SELECT password FROM passwords WHERE lid_id = %d', $member->get_id()));

			// Old md5 password
			if (preg_match('/^[a-z0-9]{32}$/', $stored_password)) {
				if (md5($password) !== $stored_password)
					return false;
			}

			// New PHP 5.5 password function crypt-like passwords
			else if (!password_verify($password, $stored_password))
				return false;

			if (password_needs_rehash($stored_password, PASSWORD_DEFAULT))
				$this->set_password($iter, $password);

			return true;
		}

		public function set_password(DataIterMember $member, $new_password)
		{
			// Todo: If we are sure we have PSQL 9.5 or higher, we could do an INSERT .. ON CONFLICT UPDATE query.

			try {
				$hash = password_hash($new_password, PASSWORD_DEFAULT);

				$this->db->insert('passwords', ['lid_id' => $member->get_id(), 'password' => $hash]);

				return true;
			} catch (PDOException $e) {
				// Assume the exception is a conflicting row. If it is not, rethrow!
				if ($e->getCode() != '23505')
					throw $e;
				
				$affected = $this->db->update('passwords',
					['password' => $hash],
					sprintf('lid_id = %d', $member->get_id()));

				return $affected === 1;
			}
		}

		/**
		  * Get commissies a certain member is in
		  * @memberid the id of the member
		  *
		  * @result an array of commissie ids
		  */
		public function get_commissies($memberid)
		{
			$rows = $this->db->query("SELECT committee_id
					FROM committee_members
					WHERE member_id = " . intval($memberid));

			$commissies = array();

			if (!$rows)
				return $commissies;

			foreach ($rows as $row)
				$commissies[] = $row['committee_id'];

			return $commissies;
		}

		/**
		  * Get a member from an email address
		  * @email the email address of the member
		  *
		  * @result a #DataIter or null of there is no member with
		  * such an email address
		  */
		public function get_from_email($email)
		{
			return $this->find_one(['email__cieq' => $email]);
		}

		/**
		  * Get the full name from a iter
		  * @iter a #DataIter
		  *
		  * @result the members full name
		  */
		public function get_full_name(DataIter $iter)
		{
			return $iter['voornaam'] . ($iter['tussenvoegsel'] ? (' ' . $iter['tussenvoegsel']) : '') . ' ' . $iter['achternaam'];
		}

		/**
		  * Get all the privacy fields
		  *
		  * @result an array of privacy_field_name => privacy_field_id
		  */
		public function get_privacy()
		{
			$rows = $this->db->query('SELECT * FROM profielen_privacy ORDER BY id ASC');

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
		public function is_private(DataIter $iter, $field, $self=false)
		{
			$value = $this->get_privacy_for_field($iter,$field);
			
			// If we are viewing ourself ourselves, then it isn't private, obviously ;)
			if (get_auth()->logged_in() && $self && get_identity()->get('id') == $iter->get_id())
				return false;

			// Visible to none -> private.
			if ($value == self::VISIBLE_TO_NONE)
				return true;

			// Visible to all -> not private.
			elseif ($value == self::VISIBLE_TO_EVERYONE)
				return false;
			
			// Only visible to members, and I am not a member? -> private.
			elseif (($value & self::VISIBLE_TO_MEMBERS) && !get_identity()->member_is_active())
				return true;
			
			// Otherwise, not private
			else
				return false;
		}

		public function is_visible($iter)
		{
			return in_array($iter['type'], $this->visible_types);
		}

		/**
		  * Return the privacy value for a field
		  * @result integer that corresponds to privacy
		  */

		public function get_privacy_for_field(DataIter $iter, $field)
		{
			static $privacy = null;

			// Hack for these three fields which are often combined.
			if (in_array($field, array('voornaam', 'tussenvoegsel', 'achternaam')))
				$field = 'naam';

			if ($privacy == null)
				$privacy = $this->get_privacy();

			if (!array_key_exists($field, $privacy))
				return false;

			$value = ($iter['privacy'] >> ($privacy[$field] * 3)) & 7;
			return $value;
		}

		/*
		 * Returns true if field is viewable for all
		 *
		 */
		public function privacy_public_for_field(DataIter $iter, $field)
		{
			$value = $this->get_privacy_for_field($iter,$field);
			return ($value & self::VISIBLE_TO_EVERYONE);
		}

		/**
		  * Get members by searching in their first and last names.
		  * Only a part of the name needs to be matched.
		  *
		  *	TODO This method is only used by the YearbookCee export
		  * currently because it allows us to return all but only
		  * relevant members easily. Should be rewritten to use
		  * DataIter::find() or ::get() because the search functionality
		  * isn't used at all.
		  *
		  * @first a part of the first name to search for
		  * @last a part of the last name to search for
		  *
		  * @result an array of #DataIter
		  */
		public function get_from_search_first_last($first, $last)
		{

			$query = 'SELECT l.*, s.studie
				FROM leden l
				LEFT JOIN studies s ON s.lidid = l.id
				WHERE l.type IN (' . implode(',', $this->visible_types) . ') ';

			$order = array();

			if ($first) {
				$query .= " AND l.voornaam ILIKE '%" . $this->db->escape_string($first) . "%'";
				$order[] = 'l.voornaam';
			}

			if ($last) {
				$query .= " AND l.achternaam ILIKE '%" . $this->db->escape_string($last) . "%'";
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
		public function search_name($name, $limit = null)
		{
			if (!$name)
				return array();

			$name = $this->db->escape_string($name);

			$query = "SELECT
					leden.*,
					COUNT(DISTINCT foto_faces.id) as number_of_tags,
					COUNT(DISTINCT committee_members.committee_id) number_of_committees
					FROM
						leden
					LEFT JOIN committee_members ON
						committee_members.member_id = leden.id
					LEFT JOIN foto_faces ON
						foto_faces.lid_id = leden.id
					WHERE
						type IN (" . implode(',', $this->visible_types) . ")
						AND (
							unaccent(lower(CASE
								WHEN coalesce(tussenvoegsel, '') = '' THEN
									voornaam || ' ' || achternaam
								ELSE
									voornaam || ' ' || tussenvoegsel || ' ' || achternaam
							END)) ILIKE unaccent('%{$name}%')
							OR unaccent(leden.nick) ILIKE unaccent('%{$name}%')
						)
					GROUP BY
						leden.id
					ORDER BY
						number_of_tags DESC,
						number_of_committees DESC,
						leden.voornaam ASC";

			if ($limit !== null)
				$query .= sprintf(' LIMIT %d', $limit);

			$rows = $this->db->query($query);

			$members = $this->_rows_to_iters($rows);

			// Filter out people who don't show their name
			// Except when you are the board! The board can do anything!
			// All hail the Board!
			// Hail AC/DCee too!
			if (!get_identity()->member_in_committee(COMMISSIE_BESTUUR)
				&& !get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
				$members = array_filter($members, function($member) {
					return !$this->is_private($member, 'naam');
				});

			//'rebase' the array so PHP doesn't forget to count properly starting from zero ;)
			return array_values($members);
		}

		public function search($query, $limit = null)
		{
			return $this->search_name($query, $limit);
		}

		/**
		  * Get members by searching for their starting year
		  *
		  * @result an array of #DataIter
		  */
		public function get_from_search_year($year)
		{
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
		public function get_distinct_years()
		{
			$rows = $this->db->query("SELECT DISTINCT beginjaar
						FROM leden
						WHERE type IN (" . implode(',', $this->visible_types) . ")
						ORDER BY beginjaar ASC");
			$rows = $this->_rows_to_iters($rows);
			$years = array();
			foreach ($rows as $row) {
				array_push($years,$row['beginjaar']);
			}
			return $years;
		}

		public function get_from_status($status)
		{
			$rows = $this->db->query("SELECT *
					FROM leden
					WHERE type =  " . intval($status)  . "
					ORDER BY voornaam");

			return $this->_rows_to_iters($rows);
		}

		public function get_status($iter)
		{
			switch ($iter['type'])
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

				case MEMBER_STATUS_UNCONFIRMED:
					return __('Geen status');

				default:
					return __('Onbekend');
			}
		}

		public function get_from_facebook_ids(array $ids)
		{
			$sql_ids = array_map(function($id) {
				return sprintf("'%s'", $this->db->escape_string($id));
			}, $ids);

			$rows = $this->db->query("SELECT leden.*, facebook.data_value as facebook_id
					FROM facebook
					RIGHT JOIN leden ON leden.id = facebook.lid_id
					WHERE facebook.data_key = 'user_id' and facebook.data_value IN ($sql_ids)");

			$members = array();

			foreach ($this->_rows_to_iters($rows) as $iter)
				$members[$iter['facebook_id']] = $iter;

			return $members;
		}

		/**
		  * Check if a member already exists
		  * @memberid the member id
		  *
		  * @result true if the member id is already used, false
		  * otherwise
		  */
		public function exists($memberid)
		{
			$val = $this->db->query_value('SELECT 1
					FROM leden
					WHERE id = ' . intval($memberid));

			return ($val == 1);
		}
	}
?>
