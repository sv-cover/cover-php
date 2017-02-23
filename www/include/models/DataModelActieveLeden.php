<?php
	require_once 'include/data/DataModel.php';

	/**
	  * A class implementing active member data
	  */
	class DataModelActieveLeden extends DataModel
	{
		public function __construct($db)
		{
			parent::__construct($db, 'committee_members');
		}
		
		public function get_active_members($type = null, $include_hidden = true)
		{
			$committee_conditions = "";

			if ($type !== null)
				$committee_conditions .= sprintf('AND c.type = %d', $type);

			if (!$include_hidden)
				$committee_conditions .= 'AND c.hidden = 0';

			$rows = $this->db->query("SELECT DISTINCT
				l.id,
				l.voornaam,
				l.tussenvoegsel,
				l.achternaam,
				l.email,
				l.privacy,
				array_agg(c.id) as commissie_ids,
				COUNT(c.id) as commissie_count
				FROM
					committee_members a
				LEFT JOIN leden l ON
					a.member_id = l.id
				RIGHT JOIN commissies c ON 
					a.committee_id = c.id
					$committee_conditions
				GROUP BY
					l.id,
					l.voornaam,
					l.tussenvoegsel,
					l.achternaam,
					l.email,
					l.privacy
				ORDER BY
					voornaam,
					tussenvoegsel,
					achternaam ASC
			");

			return $this->_rows_to_iters($rows);
		}
	}
