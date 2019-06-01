<?php

require_once 'include/data/DataModel.php';
require_once 'include/models/DataModelMember.php'; // Required for MEMBER_STATUS_LID_AF
require_once 'include/email.php';

class DataIterMailinglist extends DataIter
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
			'commissie', // Why didn't I correctly name it committee_id?! :(
			'tag',
			'has_members',
			'has_contributors',
			'has_starting_year',
			'on_subscription_subject',
			'on_subscription_message',
			'on_first_email_subject',
			'on_first_email_message',
		];
	}

	static public function rules()
	{
		return [
			'adres' => [
				'clean' => 'strtolower',
				'required' => true,
				'validate' => ['email', 'not_empty']
			],
			'naam' => [
				'required' => true,
				'validate' => ['not_empty']
			],
			'omschrijving' => [
				'required' => true
			],
			'type' => [
				'clean' => 'intval',
				'validate' => [
					'not_empty',
					function($type) {
						return in_array($type, [
							DataModelMailinglist::TYPE_OPT_IN,
							DataModelMailinglist::TYPE_OPT_OUT
						]);
					}
				]
			],
			'publiek' => [
				'clean' => function($value) {
					// Bit of a hack because I chose to use the boolean type here in the
					// database table instead of just an integer, which is used more often
					return new DatabaseLiteral($value ? 'TRUE' : 'FALSE');
				}
			],
			'toegang' => [
				'required' => true,
				'clean' => 'intval',
				'validate' => [
					'not_empty',
					function($toegang) {
						return in_array($toegang, [
							DataModelMailinglist::TOEGANG_IEDEREEN,
							DataModelMailinglist::TOEGANG_DEELNEMERS,
							DataModelMailinglist::TOEGANG_COVER,
							DataModelMailinglist::TOEGANG_EIGENAAR,
						]);
					}
				]
			],
			'commissie' => [
				'required' => true,
				'validate' => [
					'not_empty',
					'committee'
				]
			],
			'tag' => [
				'required' => true
			],
			'has_members' => [
				'clean' => function($value) {
					return new DatabaseLiteral($value ? 'TRUE' : 'FALSE');
				}				
			],
			'has_contributors' => [
				'clean' => function($value) {
					return new DatabaseLiteral($value ? 'TRUE' : 'FALSE');
				}
			],
			'has_starting_year' => [
				'clean' => function($value) {
					return $value ? intval($value) : null;
				}
			],
			'on_subscription_subject' => [
				'validate' => []
			],
			'on_subscription_message' => [
				'validate' => []
			],
			'on_first_email_subject' => [
				'validate' => []
			],
			'on_first_email_message' => [
				'validate' => []
			]
		];
	}

	public function bevat_lid(DataIterMember $member)
	{
		return get_model('DataModelMailinglistSubscription')->is_subscribed($this, $member);
	}

	public function sends_email_on_subscribing()
	{
		return strlen($this['on_subscription_subject']) > 0
			&& strlen($this['on_subscription_message']) > 0;
	}

	public function sends_email_on_first_email()
	{
		return strlen($this['on_first_email_subject']) > 0
			&& strlen($this['on_first_email_message']) > 0;
	} 

	public function get_subscriptions()
	{
		return get_model('DataModelMailinglistSubscription')->get_subscriptions($this);
	}

	public function get_committee()
	{
		return get_model('DataModelCommissie')->get_iter($this['commissie']);
	}

	public function get_archive()
	{
		return new DataModelMailinglistArchiveAdaptor($this);
	}

	public function get_reach($partition_by = null)
	{
		return get_model('DataModelMailinglistSubscription')->get_reach($this, $partition_by);
	}
}

class DataModelMailinglistArchiveAdaptor
{
	protected $model;

	protected $mailing_list;
	
	public function __construct(DataIterMailinglist $mailing_list)
	{
		$this->model = get_model('DataModelMailinglistArchive');

		$this->mailing_list = $mailing_list;
	}

	public function contains_email_from($sender)
	{
		return $this->model->contains_email_from($this->mailing_list, $sender);
	}

	public function get()
	{
		return $this->model->get_for_list($this->mailing_list);
	}

	public function count($span_in_days = null)
	{
		return $this->model->count_for_list($this->mailing_list, $span_in_days);
	}
}

class DataModelMailinglist extends DataModel
{
	const TOEGANG_IEDEREEN = 1;
	const TOEGANG_DEELNEMERS = 2;
	const TOEGANG_COVER = 3;
	const TOEGANG_EIGENAAR = 4;

	const TYPE_OPT_IN = 1;
	const TYPE_OPT_OUT = 2;

	public $dataiter = 'DataIterMailinglist';

	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten');
	}

	public function _row_to_iter($row, $dataiter = null, array $preseed = [])
	{
		// Stupid PGSQL boolean stuff...
		if ($row && isset($row['publiek']))
			$row['publiek'] = $row['publiek'] == 't';

		if ($row && isset($row['has_members']))
			$row['has_members'] = $row['has_members'] == 't';

		if ($row && isset($row['has_contributors']))
			$row['has_contributors'] = $row['has_contributors'] == 't';

		if ($row && isset($row['subscribed']))
			$row['subscribed'] = $row['subscribed'] == 't';

		return parent::_row_to_iter($row, $dataiter, $preseed);
	}

	public function get_for_member(DataIterMember $member, $public_only = true)
	{
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
				AND a.lid_id = ' . intval($member['id']) . '
				AND (a.opgezegd_op > NOW() OR a.opgezegd_op IS NULL)
			LEFT JOIN
				mailinglijsten_opt_out o
				ON o.mailinglijst_id = l.id
				AND o.lid_id = ' . intval($member['id']) . '
				AND o.opgezegd_op < NOW()
			GROUP BY
				l.id,
				l.naam
			ORDER BY
				l.naam ASC');

		return $this->_rows_to_iters($rows);
	}

	public function get_iter_by_address($address)
	{
		return $this->find_one(['adres' => $address]);
	}

	public function send_subscription_mail(DataIterMailinglist $lijst, $naam, $email)
	{
		if (!$lijst->sends_email_on_subscribing())
			return;

		$text = $lijst->get('on_subscription_message');

		$variables = array(
			'[NAAM]' => htmlspecialchars($naam, ENT_COMPAT, 'utf-8'),
			'[NAME]' => htmlspecialchars($naam, ENT_COMPAT, 'utf-8'),
			'[MAILINGLIST]' => htmlspecialchars($lijst->get('naam'), ENT_COMPAT, 'utf-8')
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

		$message->setHeader('From', 'Cover Mail Monkey <monkies@svcover.nl>');
		$message->setHeader('Reply-To', 'Cover WebCie <webcie@ai.rug.nl>');
		$message->addBody('text/plain', strip_tags($personalized_message));
		$message->addBody('text/html', $personalized_message);

		list($message_headers, $message_body) = preg_split("/\r?\n\r?\n/", $message->toString(), 2);

		return mail(sprintf('%s <%s>', $naam, $email), $subject, $message_body, $message_headers);
	}

	public function member_can_access_archive(DataIterMailinglist $lijst)
	{
		if (!get_auth()->logged_in())
			return false;

		if ($lijst->bevat_lid(get_identity()->member()))
			return true;

		if (get_identity()->member_in_committee($lijst['commissie']))
			return true;

		if (get_identity()->member_in_committee(COMMISSIE_EASY))
			return true;

		return false;
	} 
}
