<?php
	require_once 'include/data/DataModel.php';

	class DataIterMembership extends DataIter
	{
		public function get_duration()
		{
			if (!$this->get('started_on'))
				return null;
			else
				$started_on = new DateTime($this->get('started_on'));

			if ($this->get('discharged_on'))
				$discharged_on = new DateTime($this->get('discharged_on'));
			else
				$discharged_on = new DateTime();

			return $discharged_on->diff($started_on);
		}

		public function __toString()
		{
			return sprintf('<%d>[member#%d in committee#%d from %s to %s (%d days)]',
				$this->get_id(),
				$this->get('lidid'),
				$this->get('commissieid'),
				$this->get('started_on'),
				$this->get('discharged_on'),
				$this->get_duration() ? $this->get_duration()->days : 0);
		}
	}

	/**
	  * A class implementing active member data
	  */
	class DataModelActieveLeden extends DataModel
	{
		public $fields = array(
			'id',
			'lidid',
			'commissieid',
			'functie',
			'started_on',
			'discharged_on'
		);

		public $dataiter = 'DataIterMembership';

		public function __construct($db)
		{
			parent::__construct($db, 'actieveleden');
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
				WHERE a.discharged_on IS NULL
				GROUP BY l.id, l.voornaam, l.tussenvoegsel, l.achternaam, l.email, l.privacy
				ORDER BY voornaam, tussenvoegsel, achternaam ASC');

			return $this->_rows_to_iters($rows);
		}

		public function start_membership(DataIterCommissie $committee, DataIterMember $member, $functie = null, DateTime $timestamp = null)
		{
			if ($timestamp && ($prev_membership = $this->find_membership($committee, $member, $timestamp)))
				throw new LogicException('Person is already member of the committee: ' . $prev_membership);

			$data = array(
				'commissieid' => $committee->get_id(),
				'lidid' => $member->get_id(),
				'functie' => $functie,
				'started_on' => $timestamp ? $timestamp->format('Y-m-d') : null);

			$this->db->insert('actieveleden', $data);

			$iter_factory = new ReflectionClass($this->dataiter);
			return $iter_factory->newInstance($this, $this->db->get_last_insert_id(), $data);
		}

		public function find_membership(DataIterCommissie $committee, DataIterMember $member, DateTime $timestamp)
		{
			$row = $this->db->query_first(sprintf("SELECT *
				FROM
					{$this->table} 
				WHERE
					commissieid = %d
					AND lidid = %d
					AND (discharged_on IS NULL OR discharged_on >= '%s')",
				$committee->get_id(),
				$member->get_id(),
				$timestamp ? $timestamp->format('Y-m-d') : null));

			return $this->_row_to_iter($row);
		}

		public function end_membership($membership_id, DateTime $timestamp)
		{
			$iter = $this->get_iter($membership_id);

			if ($iter->get('discharged_on') && $iter->get('discharged_on') != $timestamp->format('Y-m-d'))
				throw new LogicException(sprintf('Committee membership %s cannot be ended on %s because it has already ended on %s',
					$iter, $timestamp->format('Y-m-d'), $iter->get('discharged_on')));

			$this->db->update('actieveleden',
				array('discharged_on' => $timestamp->format('Y-m-d')),
				sprintf('id = %d', $membership_id));
		}

		public function update_membership($membership_id, $functie)
		{
			$this->db->update('actieveleden',
				array('functie' => $functie),
				sprintf('id = %d', $membership_id));
		}

		protected function _generate_query($where)
		{
			if ($where)
				$where = "WHERE {$where}";
			
			return "SELECT
					{$this->table}.id,
					{$this->table}.lidid,
					{$this->table}.commissieid,
					{$this->table}.functie,
					{$this->table}.started_on,
					{$this->table}.discharged_on,
					l.id lid__id,
					l.voornaam lid__voornaam,
					l.tussenvoegsel lid__tussenvoegsel,
					l.achternaam lid__achternaam,
					l.email lid__email,
					l.privacy lid__privacy,
					c.id commissie__id,
					c.naam commissie__naam,
					c.login commissie__login,
					c.website commissie__website,
					c.nocaps commissie__nocaps,
					c.page commissie__page,
					c.hidden commissie__hidden,
					c.vacancies commissie__vacancies
				FROM
					{$this->table}
				INNER JOIN leden l ON
					l.id = {$this->table}.lidid
				INNER JOIN commissies c ON
					c.id = {$this->table}.commissieid
				{$where}
				GROUP BY
					{$this->table}.id,
					l.id,
					c.id
				ORDER BY
					{$this->table}.started_on DESC NULLS LAST,
					{$this->table}.id DESC";
		}
	}
