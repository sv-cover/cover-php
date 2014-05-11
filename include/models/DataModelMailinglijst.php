<?php

require_once 'data/DataModel.php';
require_once 'models/DataModelMember.php'; // Required for MEMBER_STATUS_LID_AF

class DataModelMailinglijst extends DataModel
{
	const TOEGANG_IEDEREEN = 1;
	const TOEGANG_DEELNEMERS = 2;
	const TOEGANG_COVER = 3;
	const TOEGANG_EIGENAAR = 4;

	const TYPE_OPT_IN = 1;
	const TYPE_OPT_OUT = 2;

	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten');

		$this->model_aanmeldingen = new DataModel($db, 'mailinglijsten_abonnementen', 'abonnement_id');

		$this->model_opt_out = new DataModel($db, 'mailinglijsten_opt_out');
	}

	public function _row_to_iter($row)
	{
		if ($row && isset($row['publiek']))
			$row['publiek'] = $row['publiek'] == 't';

		if ($row && isset($row['subscribed']))
			$row['subscribed'] = $row['subscribed'] == 't';

		return parent::_row_to_iter($row);
	}

	public function get_lijsten($lid_id, $public_only = true)
	{
		if ($public_only)
			if ($commissies = logged_in('commissies'))
				$where_clause = 'WHERE (l.publiek = TRUE OR l.commissie IN (' . implode(', ', $commissies) . '))';
			else
				$where_clause = 'WHERE l.publiek = TRUE';
		else
			$where_clause = '';

		// FIXME deze query houdt geen rekening met leden.type = MEMBER_STATUS_LID
		// voor opt-out lijsten en leden.type <> MEMBER_STATUS_LID_AF voor opt-in
		// lijsten.
		$rows = $this->db->query('
			SELECT
				l.id,
				l.naam, 
				l.adres,
				l.omschrijving,
				l.publiek,
				l.type,
				l.toegang,
				l.commissie,
				CASE
					WHEN l.type = ' . self::TYPE_OPT_IN . ' THEN COUNT(a.abonnement_id) > 0
					WHEN l.type = ' . self::TYPE_OPT_OUT . ' THEN COUNT(o.id) = 0
					ELSE FALSE
				END as subscribed
			FROM
				mailinglijsten l
			LEFT JOIN
				mailinglijsten_abonnementen a
				ON a.mailinglijst_id = l.id
				AND a.lid_id = ' . intval($lid_id) . '
				AND (a.opgezegd_op > NOW() OR a.opgezegd_op IS NULL)
			LEFT JOIN
				mailinglijsten_opt_out o
				ON o.mailinglijst_id = l.id
				AND o.lid_id = ' . intval($lid_id) . '
				AND o.opgezegd_op < NOW()
			' . $where_clause . '
			GROUP BY
				l.id,
				l.naam,
				l.adres,
				l.omschrijving,
				l.publiek,
				l.type,
				l.toegang,
				l.commissie
			ORDER BY
				l.naam ASC');

		return $this->_rows_to_iters($rows);
	}

	public function get_lijst($lijst_id)
	{
		if (is_numeric($lijst_id))
			$query = sprintf('l.id = %d', $lijst_id);
		else
			$query = sprintf("l.adres = '%s'", $this->db->escape_string(strtolower($lijst_id)));
		
		$row = $this->db->query_first('
			SELECT
				l.id,
				l.naam,
				l.adres,
				l.omschrijving,
				l.publiek,
				l.type,
				l.toegang,
				l.commissie
			FROM
				mailinglijsten l
			WHERE
				' . $query);

		return $this->_row_to_iter($row);
	}

	public function create_lijst($adres, $naam, $omschrijving, $publiek, $type, $toegang, $commissie)
	{
		if (!filter_var($adres, FILTER_VALIDATE_EMAIL))
			throw new InvalidArgumentException('Invalid adres');

		if (strlen($naam) == 0)
			throw new InvalidArgumentException('Empty naam');

		if (!in_array((int) $type, array(
			self::TYPE_OPT_IN,
			self::TYPE_OPT_OUT)))
			throw new InvalidArgumentException('Invalid type');

		if (!in_array((int) $toegang, array(
			self::TOEGANG_IEDEREEN,
			self::TOEGANG_DEELNEMERS,
			self::TOEGANG_COVER,
			self::TOEGANG_EIGENAAR)))
			throw new InvalidArgumentException('Invalid value for toegang');

		if (!get_model('DataModelCommissie')->get_iter($commissie))
			throw new InvalidArgumentException('Invalid commissie');

		$data = array(
			'adres' => strtolower($adres),
			'naam' => $naam,
			'omschrijving' => $omschrijving,
			'publiek' => $publiek ? '1' : '0',
			'type' => intval($type),
			'toegang' => $toegang,
			'commissie' => $commissie
		);

		$iter = new DataIter($this, -1, $data);

		return $this->insert($iter, true);
	}

	public function get_aanmeldingen(DataIter $lijst)
	{
		switch ($lijst->get('type'))
		{
			case self::TYPE_OPT_IN:
				$rows = $this->db->query(sprintf('
					SELECT
						m.abonnement_id,
						l.id as lid_id,
						coalesce(l.voornaam, m.naam) as naam,
						coalesce(l.email, m.email) as email
					FROM
						mailinglijsten_abonnementen m
					LEFT JOIN leden l ON
						m.lid_id = l.id
					WHERE
						m.mailinglijst_id = %d
						AND (l.type IS NULL OR l.type <> ' . MEMBER_STATUS_LID_AF . ')
						AND (m.opgezegd_op > NOW() OR m.opgezegd_op IS NULL)
					ORDER BY
						naam ASC',
					$lijst->get('id')));
				break;

			case self::TYPE_OPT_OUT:
				$rows = $this->db->query(sprintf('
					SELECT
						NULL as abonnement_id,
						l.id as lid_id,
						l.voornaam as naam,
						l.email
					FROM
						leden l
					LEFT JOIN mailinglijsten_opt_out o ON
						o.mailinglijst_id = %d
						AND o.lid_id = l.id
					WHERE
						l.type = ' . MEMBER_STATUS_LID . '
						AND (o.opgezegd_op > NOW() OR o.opgezegd_op IS NULL) -- filter out the valid opt-outs
					UNION SELECT -- union the guest subscriptions
						g.abonnement_id,
						NULL as lid_id,
						g.naam,
						g.email
					FROM
						mailinglijsten_abonnementen g
					WHERE
						g.mailinglijst_id = %1$d
						AND (g.opgezegd_op > NOW() OR g.opgezegd_op IS NULL)
					ORDER BY
						naam ASC',
					$lijst->get('id')));
				break;
		}


		return $this->_rows_to_iters($rows);
	}

	public function is_aangemeld(DataIter $lijst, $lid_id)
	{
		switch ($lijst->get('type'))
		{
			case self::TYPE_OPT_IN:
				return $this->find_abonnement_id($lijst, $lid_id) != null;

			case self::TYPE_OPT_OUT:
				$row = $this->db->query_first(sprintf('
					SELECT
						COUNT(o.id)
					FROM
						mailinglijsten_opt_out o
					WHERE
						o.mailinglijst_id = %d
						AND o.lid_id = %d
						AND o.opgezegd_op <= NOW()',
					$lijst->get('id'), $lid_id));
		}
	}

	public function find_abonnement_id(DataIter $lijst, $lid_id)
	{
		if ($lijst->get('type') != self::TYPE_OPT_IN)
			throw new RuntimeException('You can only query the abonnement id for opt-in mailing lists');

		$row = $this->db->query_first(sprintf('
			SELECT
				m.abonnement_id
			FROM
				mailinglijsten_abonnementen m
			WHERE
				m.mailinglijst_id = %d
				AND m.lid_id = %d
				AND (m.opgezegd_op IS NULL OR m.opgezegd_op > NOW())',
			$lijst->get('id'), $lid_id));
		
		return $row ? $row['abonnement_id'] : null;
	}

	public function get_abonnement($abonnement_id)
	{
		$row = $this->db->query_first(sprintf("
			SELECT
				l.id,
				l.naam, 
				l.adres,
				l.omschrijving,
				l.publiek,
				l.type,
				l.toegang,
				l.commissie,
				a.abonnement_id
			FROM
				mailinglijsten_abonnementen a,
				mailinglijsten l
			WHERE
				a.abonnement_id = '%s'
				AND (a.opgezegd_op IS NULL OR a.opgezegd_op > NOW())
				AND l.type = " . self::TYPE_OPT_IN . "
				AND l.id = a.mailinglijst_id",
				$this->db->escape_string($abonnement_id)));

		return $this->_row_to_iter($row);
	}

	public function aanmelden(DataIter $lijst, $lid_id)
	{
		if ($this->is_aangemeld($lijst, $lid_id))
			return;

		switch ($lijst->get('type'))
		{
			// Opt in list: add a subscription to the table
			case self::TYPE_OPT_IN:
				$data = array(
					'abonnement_id' => sha1(uniqid('', true)),
					'lid_id' => intval($lid_id),
					'mailinglijst_id' => intval($lijst->get('id'))
				);

				$iter = new DataIter($this->model_aanmeldingen, -1, $data);

				return $this->model_aanmeldingen->insert($iter);

			// Opt out list: remove any opt-out entries from the table
			case self::TYPE_OPT_OUT:
				return $this->db->delete($this->model_opt_out->table,
					sprintf('lid_id = %d AND mailinglijst_id = %d',
						$lid_id, $lijst->get('id')));

			default:
				throw new RuntimeException('Subscribing to unknown list type not supported');
		}
	}

	public function aanmelden_gast(DataIter $lijst, $naam, $email)
	{
		// See if there is already a subscription for this email address
		$abonnementen = $this->model_aanmeldingen->find("email = '" . $this->db->escape_string($email) . "' AND mailinglijst_id = " . $lijst->get('id'));

		// If so, update the name
		if ($abonnementen)
		{
			$abonnement = $abonnementen[0];

			$abonnement->set('naam', $naam);

			return $this->model_aanmeldingen->update($abonnement);
		}
		// Else, there is no subscription yet
		else
		{
			$data = array(
				'abonnement_id' => sha1(uniqid('', true)),
				'naam' => $naam,
				'email' => $email,
				'mailinglijst_id' => intval($lijst->get('id'))
			);

			$iter = new DataIter($this->model_aanmeldingen, -1, $data);

			return $this->model_aanmeldingen->insert($iter);
		}
	}

	public function afmelden(DataIter $lijst, $lid_id)
	{
		switch ($lijst->get('type'))
		{
			// For opt-in lists: find the abonnement and delete it.
			case self::TYPE_OPT_IN:
				// Find the abonnement id
				$abonnement_id = $this->find_abonnement_id($lijst, $lid_id);
				
				// and unsubscribe using that id
				return $this->afmelden_via_abonnement_id($abonnement_id);

			// For opt-out lists: add an opt-out entry.
			case self::TYPE_OPT_OUT:
				$data = array(
					'mailinglijst_id' => intval($lijst->get('id')),
					'lid_id' => intval($lid_id)
				);

				$iter = new DataIter($this->model_opt_out, -1, $data);

				return $this->model_opt_out->insert($iter);
		}
	}

	public function afmelden_via_abonnement_id($abonnement_id)
	{
		return $this->db->update(
			$this->model_aanmeldingen->table,
			array('opgezegd_op' => 'NOW()'),
			sprintf("abonnement_id = '%s'",
				$this->db->escape_string($abonnement_id)),
			array('opgezegd_op'));
	}

	public function member_can_edit($lijst)
	{
		if (is_numeric($lijst))
			$lijst = $this->get_iter($lijst);

		if (!$lijst)
			return false;

		return member_in_commissie(COMMISSIE_BESTUUR)
			|| member_in_commissie($lijst->get('commissie'));
	}

	public function member_can_subscribe($lijst)
	{
		if (member_in_commissie(COMMISSIE_BESTUUR))
			return true;

		// You cannot subscribe yourself to a non-public list
		if (!$lijst->get('publiek'))
			return false;

		// You cannot 'subscribe' (opt back in) to an opt-out list if you are not a member
		if ($lijst->get('type') == self::TYPE_OPT_OUT && logged_in('status') != MEMBER_STATUS_LID)
			return false;

		return true;
	}

	public function member_can_unsubscribe($lijst)
	{
		if (member_in_commissie(COMMISSIE_BESTUUR))
			return true;

		// You cannot unsubscribe from non-public lists
		if (!$lijst->get('publiek'))
			return false;

		// Any other list if perfectly fine.
		return true;
	}
}