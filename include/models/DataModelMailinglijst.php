<?php

require_once 'include/data/DataModel.php';
require_once 'include/models/DataModelMember.php'; // Required for MEMBER_STATUS_LID_AF
require_once 'include/email.php';

class DataIterMailinglijst extends DataIter
{
	static public function fields()
	{
		return [
			'id',
			'naam',
			'adres',
			'omschrijving',
			'type',
			'publiek',
			'toegang',
			'commissie',
			'tag',
		];
	}

	public function bevat_lid($lid_id)
	{
		return $this->model->is_aangemeld($this, $lid_id);
	}

	public function sends_email_on_subscribing()
	{
		return strlen($this->get('on_subscription_subject')) > 0
			&& strlen($this->get('on_subscription_message')) > 0;
	}

	public function sends_email_on_first_email()
	{
		return strlen($this->get('on_first_email_subject')) > 0
			&& strlen($this->get('on_first_email_message')) > 0;
	} 

	public function archive()
	{
		return new DataModelMailinglijstArchiefAdapator($this);
	}
}

class DataModelMailinglijstArchiefAdapator
{
	protected $model;

	protected $lijst;
	
	public function __construct(DataIterMailinglijst $lijst)
	{
		$this->model = get_model('DataModelMailinglijstArchief');

		$this->lijst = $lijst;
	}

	public function contains_email_from($sender)
	{
		return $this->model->contains_email_from($this->lijst, $sender);
	}
}

class DataModelMailinglijst extends DataModel
{
	const TOEGANG_IEDEREEN = 1;
	const TOEGANG_DEELNEMERS = 2;
	const TOEGANG_COVER = 3;
	const TOEGANG_EIGENAAR = 4;

	const TYPE_OPT_IN = 1;
	const TYPE_OPT_OUT = 2;

	/* protected */ var $dataiter = 'DataIterMailinglijst';

	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten');

		$this->model_aanmeldingen = new DataModel($db, 'mailinglijsten_abonnementen', 'abonnement_id');

		$this->model_opt_out = new DataModel($db, 'mailinglijsten_opt_out');
	}

	public function _row_to_iter($row, $dataiter = null)
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
				l.*,
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
				l.naam
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
				l.*
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
						coalesce(l.email, m.email) as email,
						l.id as lid__id,
						l.voornaam as lid__voornaam,
						l.tussenvoegsel as lid__tussenvoegsel,
						l.achternaam as lid__achternaam,
						l.privacy as lid__privacy
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
						l.email,
						l.id as lid__id,
						l.voornaam as lid__voornaam,
						l.tussenvoegsel as lid__tussenvoegsel,
						l.achternaam as lid__achternaam,
						l.privacy as lid__privacy
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
						g.email,
						NULL as lid__id,
						NULL as lid__voornaam,
						NULL as lid__tussenvoegsel,
						NULL as lid__achternaam,
						NULL as lid__privacy
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
				l.*,
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
		$lid = get_model('DataModelMember')->get_iter($lid_id);

		if ($this->is_aangemeld($lijst, $lid->get_id()))
			return;

		$id = $this->insert_aanmelding_lid($lijst, $lid);

		$this->stuur_aanmeldingsmail($lijst, member_full_name($lid, IGNORE_PRIVACY), $lid->get('email'));

		return $id;
	}

	protected function insert_aanmelding_lid(DataIter $lijst, DataIterMember $lid)
	{
		switch ($lijst->get('type'))
		{
			// Opt in list: add a subscription to the table
			case self::TYPE_OPT_IN:
				$data = array(
					'abonnement_id' => sha1(uniqid('', true)),
					'lid_id' => $lid->get_id(),
					'mailinglijst_id' => intval($lijst->get('id'))
				);

				$iter = new DataIter($this->model_aanmeldingen, -1, $data);

				return $this->model_aanmeldingen->insert($iter);

			// Opt out list: remove any opt-out entries from the table
			case self::TYPE_OPT_OUT:
				return $this->db->delete($this->model_opt_out->table,
					sprintf('lid_id = %d AND mailinglijst_id = %d',
						$lid->get_id(), $lijst->get('id')));

			default:
				throw new RuntimeException('Subscribing to unknown list type not supported');
		}
	}

	public function aanmelden_gast(DataIter $lijst, $naam, $email)
	{
		$data = array(
			'abonnement_id' => sha1(uniqid('', true)),
			'naam' => $naam,
			'email' => $email,
			'mailinglijst_id' => intval($lijst->get('id'))
		);

		$iter = new DataIter($this->model_aanmeldingen, -1, $data);

		$id = $this->model_aanmeldingen->insert($iter);

		$this->stuur_aanmeldingsmail($lijst, $naam, $email);

		return $id;
	}

	protected function stuur_aanmeldingsmail(DataIter $lijst, $naam, $email)
	{
		if (!$lijst->sends_email_on_subscribing())
			return;

		$text = $lijst->get('on_subscription_message');

		$variables = array(
			'[NAAM]' => htmlspecialchars($naam, ENT_COMPAT, WEBSITE_ENCODING),
			'[NAME]' => htmlspecialchars($naam, ENT_COMPAT, WEBSITE_ENCODING),
			'[MAILINGLIST]' => htmlspecialchars($lijst->get('naam'), ENT_COMPAT, WEBSITE_ENCODING)
		);

		// If you are allowed to unsubscribe, parse the placeholder correctly (different for opt-in and opt-out lists)
		/*
		if ($lijst->get('publiek'))
		{
			$url = $lijst->get('type')== DataModelMailinglijst::TYPE_OPT_IN
				? ROOT_DIR_URI . sprintf('mailinglijsten.php?abonnement_id=%s', $aanmelding->get('abonnement_id'))
				: ROOT_DIR_URI . sprintf('mailinglijsten.php?lijst_id=%d', $lijst->get('id'));

			$variables['[UNSUBSCRIBE_URL]'] = htmlspecialchars($url, ENT_QUOTES, WEBSITE_ENCODING);

			$variables['[UNSUBSCRIBE]'] = sprintf('<a href="%s">Click here to unsubscribe from the %s mailinglist.</a>',
				htmlspecialchars($url, ENT_QUOTES, WEBSITE_ENCODING),
				htmlspecialchars($lijst->get('naam'), ENT_COMPAT, WEBSITE_ENCODING));
		}
		*/

		$subject = $lijst->get('on_first_email_subject');

		$personalized_message = str_replace(array_keys($variables), array_values($variables), $text);

		$message = new \Cover\email\MessagePart();

		$message->setHeader('To', sprintf('%s <%s>', $naam, $email));
		$message->setHeader('From', 'Cover Mail Monkey <monkies@svcover.nl>');
		$message->setHeader('Reply-To', 'Cover WebCie <webcie@ai.rug.nl>');
		$message->setHeader('Subject', $subject);
		$message->addBody('text/plain', strip_tags($personalized_message));
		$message->addBody('text/html', $personalized_message);

		list($message_headers, $message_body) = preg_split("/\r?\n\r?\n/", $message->toString(), 2);

		return mail('', $subject, $message_body, $message_headers);
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

	public function member_can_access_archive(DataIterMailinglijst $lijst)
	{
		if (!logged_in())
			return false;

		if ($lijst->bevat_lid(get_identity()->get('id')))
			return true;

		if (get_identity()->member_in_committee($lijst->get('commissie')))
			return true;

		return false;
	} 

	public function member_can_edit(DataIterMailinglijst $lijst)
	{
		trigger_error('DataModelMailinglijst::member_can_edit is deprecated in favor of using the policy', E_USER_WARNING);

		return get_policy($this)->user_can_update($lijst);
	}

	public function member_can_subscribe(DataIterMailinglijst $lijst)
	{
		trigger_error('DataModelMailinglijst::member_can_subscribe is deprecated in favor of using the policy', E_USER_WARNING);

		if ($this->member_can_edit($lijst))
			return true;

		// You cannot subscribe yourself to a non-public list
		if (!$lijst->get('publiek'))
			return false;

		// You cannot 'subscribe' (opt back in) to an opt-out list if you are not a member
		if ($lijst->get('type') == self::TYPE_OPT_OUT && get_identity()->get('type') != MEMBER_STATUS_LID)
			return false;

		return true;
	}

	public function member_can_unsubscribe(DataIterMailinglijst $lijst)
	{
		trigger_error('DataModelMailinglijst::member_can_unsubscribe is deprecated in favor of using the policy', E_USER_WARNING);

		if ($this->member_can_edit($lijst))
			return true;

		// You cannot unsubscribe from non-public lists
		if (!$lijst->get('publiek'))
			return false;

		// Any other list is perfectly fine.
		return true;
	}
}
