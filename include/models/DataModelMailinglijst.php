<?php

require_once 'data/DataModel.php';
require_once 'models/DataModelMember.php'; // Required for MEMBER_STATUS_LID_AF

class DataModelMailinglijst extends DataModel
{
	const TOEGANG_IEDEREEN = 1;
	const TOEGANG_DEELNEMERS = 2;
	const TOEGANG_COVER = 3;
	const TOEGANG_EIGENAAR = 4;

	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten');

		$this->model_aanmeldingen = new DataModel($db, 'mailinglijsten_abonnementen', 'abonnement_id');
	}

	public function _row_to_iter($row)
	{
		if ($row && isset($row['publiek']))
			$row['publiek'] = $row['publiek'] == 't';

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

		$rows = $this->db->query('
			SELECT
				l.id,
				l.naam, 
				l.adres,
				l.omschrijving,
				l.publiek,
				l.toegang,
				l.commissie,
				a.abonnement_id
			FROM
				mailinglijsten l
			LEFT JOIN
				mailinglijsten_abonnementen a
				ON a.mailinglijst_id = l.id
				AND a.lid_id = ' . intval($lid_id) . '
				AND (a.opgezegd_op > NOW() OR a.opgezegd_op IS NULL)
			' . $where_clause . '
			ORDER BY
				l.naam ASC');

		return $this->_rows_to_iters($rows);
	}

	public function get_lijst($lijst_id)
	{
		if (is_numeric($lijst_id))
			$query = sprintf('l.id = %d', $lijst_id);
		else
			$query = sprintf("l.adres = '%s'", $this->db->escape_string($lijst_id));
		
		$row = $this->db->query_first('
			SELECT
				l.id,
				l.naam,
				l.adres,
				l.omschrijving,
				l.publiek,
				l.toegang,
				l.commissie
			FROM
				mailinglijsten l
			WHERE
				' . $query);

		return $this->_row_to_iter($row);
	}

	public function create_lijst($adres, $naam, $omschrijving, $publiek, $toegang, $commissie)
	{
		if (!filter_var($adres, FILTER_VALIDATE_EMAIL))
			return false;

		if (strlen($naam) == 0)
			return false;

		if (!in_array($toegang, array(
			self::TOEGANG_IEDEREEN,
			self::TOEGANG_DEELNEMERS,
			self::TOEGANG_COVER,
			self::TOEGANG_EIGENAAR)))
			return false;

		if (!ctype_digit($commissie))
			return false;

		$data = array(
			'adres' => $adres,
			'naam' => $naam,
			'omschrijving' => $omschrijving,
			'publiek' => $publiek ? '1' : '0',
			'toegang' => $toegang,
			'commissie' => $commissie
		);

		$iter = new DataIter($this, -1, $data);

		return $this->insert($iter, true);
	}

	public function get_aanmeldingen($lijst_id)
	{
		$rows = $this->db->query(sprintf('
			SELECT
				m.abonnement_id,
				l.id,
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
			$lijst_id));

		return $this->_rows_to_iters($rows);
	}

	public function get_abonnement_id($lid_id, $lijst_id)
	{
		if (ctype_digit($lid_id))
			$query = sprintf('m.lid_id = %d', $lid_id);
		else
			$query = sprintf("m.email = '%s'", $this->db->escape_string($lid_id));

		return $this->db->query_value(sprintf("
			SELECT
				m.abonnement_id
			FROM
				mailinglijsten_abonnementen m
			WHERE
				m.mailinglijst_id = %d
				AND (m.opgezegd_op IS NULL OR m.opgezegd_op > NOW())
				AND %s",
			$lid_id, $lijst_id, $query));
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
				l.toegang,
				l.commissie,
				a.abonnement_id
			FROM
				mailinglijsten_abonnementen a,
				mailinglijsten l
			WHERE
				a.abonnement_id = '%s'
				AND (a.opgezegd_op IS NULL OR a.opgezegd_op > NOW())
				AND l.id = a.mailinglijst_id",
				$this->db->escape_string($abonnement_id)));

		return $this->_row_to_iter($row);
	}

	public function aanmelden($lid_id, $lijst_id)
	{
		if ($this->get_abonnement_id($lid_id, $lijst_id) !== null)
			return;

		$data = array(
			'abonnement_id' => sha1(uniqid('', true)),
			'lid_id' => intval($lid_id),
			'mailinglijst_id' => intval($lijst_id)
		);

		$iter = new DataIter($this->model_aanmeldingen, -1, $data);

		return $this->model_aanmeldingen->insert($iter);
	}

	public function aanmelden_gast($naam, $email, $lijst_id)
	{
		if ($this->get_abonnement_id($email, $lijst_id) !== null)
			return;

		$data = array(
			'abonnement_id' => sha1(uniqid('', true)),
			'naam' => $naam,
			'email' => $email,
			'mailinglijst_id' => intval($lijst_id)
		);

		$iter = new DataIter($this->model_aanmeldingen, -1, $data);

		return $this->model_aanmeldingen->insert($iter);
	}

	public function afmelden($abonnement_id)
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
}