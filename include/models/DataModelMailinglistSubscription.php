<?php

require_once 'include/data/DataModel.php';
require_once 'include/models/DataModelMember.php'; // Required for MEMBER_STATUS_LID_AF
require_once 'include/member.php'; // Required for member_full_name

class DataIterMailinglistSubscription extends DataIter
{
	static public function fields()
	{
		return [
			'abonnement_id',
			'mailinglijst_id',
			'lid_id',
			'naam',
			'email',
			'ingeschreven_op',
			'opgezegd_op',
		];
	}

	public function get_mailinglist()
	{
		return get_model('DataModelMailinglist')->get_iter($this['mailinglijst_id']);
	}

	public function get_lid()
	{
		if ($this['lid__id'])
			return $this->getIter('lid', 'DataIterMember');
		elseif ($this['lid_id'])
			return get_model('DataModelMember')->get_iter($this['lid_id']);
		else 
			return null;
	}
	
	public function is_active()
	{
		return empty($this['opgezegd_op']) || new DateTime($this['opgezegd_op']) > new DateTime();
	}

	public function cancel()
	{
		return $this->model->cancel_subscription($this);	
	}
}

class DataModelMailinglistSubscription extends DataModel
{
	public $dataiter = 'DataIterMailinglistSubscription';

	public function __construct($db)
	{
		parent::__construct($db, 'mailinglijsten_abonnementen', 'abonnement_id');
	}

	private function _is_opt_out_subscription_id($id, &$list_id = null, &$member_id = null)
	{
		if (preg_match('/^(\d+)\-(\d+)$/', $id, $match))
		{
			$list_id = (int) $match[1];

			$member_id = (int) $match[2];

			return true;
		}
		
		return false;
	}

	public function get_iter($id)
	{
		if ($this->_is_opt_out_subscription_id($id, $list_id, $member_id))
		{
			$row = $this->db->query_first("
				SELECT
						'{$list_id}' as mailinglijst_id,
						'{$list_id}-' || l.id as abonnement_id,
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
						o.mailinglijst_id = {$list_id}
						AND o.lid_id = l.id
					WHERE
						l.id = {$member_id}
						AND l.type = " . MEMBER_STATUS_LID . "
						AND (o.opgezegd_op > NOW() OR o.opgezegd_op IS NULL) -- filter out the valid opt-outs
			");

			if ($row === null)
				throw new DataIterNotFoundException($id, $this);

			return $this->_row_to_iter($row);
		}
		else
			return parent::get_iter($id);
	}

	public function get_subscriptions(DataIterMailinglist $lijst)
	{
		switch ($lijst['type'])
		{
			case DataModelMailinglist::TYPE_OPT_IN:
				$rows = $this->db->query(sprintf('
					SELECT
						%d as mailinglijst_id,
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
						m.mailinglijst_id = %1$d
						AND (l.type IS NULL OR l.type <> ' . MEMBER_STATUS_LID_AF . ')
						AND (m.opgezegd_op > NOW() OR m.opgezegd_op IS NULL)
					ORDER BY
						naam ASC',
					$lijst['id']));
				break;

			case DataModelMailinglist::TYPE_OPT_OUT:
				$rows = $this->db->query(sprintf("
					SELECT
						%1\$d as mailinglijst_id,
						'%1\$d-' || l.id as abonnement_id,
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
						o.mailinglijst_id = %1\$d
						AND o.lid_id = l.id
					WHERE
						l.type = " . MEMBER_STATUS_LID . "
						AND (o.opgezegd_op > NOW() OR o.opgezegd_op IS NULL) -- filter out the valid opt-outs
					UNION SELECT -- union the guest subscriptions
						%1\$d as mailinglijst_id,
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
						g.mailinglijst_id = %1\$d
						AND (g.opgezegd_op > NOW() OR g.opgezegd_op IS NULL)
					ORDER BY
						naam ASC",
					$lijst['id']));
				break;

			default:
				throw new LogicException('Invalid list type');
		}

		return $this->_rows_to_iters($rows);
	}

	public function get_reach(DataIterMailinglist $lijst, $partition_by = null)
	{
		if ($partition_by !== null && !DataIterMember::has_field($partition_by))
			throw new InvalidArgumentException("Invalid partition_by field: $partition_by not in DataIterMember::fields()");

		switch ($lijst['type'])
		{
			case DataModelMailinglist::TYPE_OPT_IN:
				$rows = $this->db->query(sprintf('
					SELECT
						COALESCE(%s, -1) as partition_group,
						COUNT(m.abonnement_id) as cnt
					FROM
						mailinglijsten_abonnementen m
					LEFT JOIN leden l ON
						m.lid_id = l.id
					WHERE
						m.mailinglijst_id = %d
						AND (l.type IS NULL OR l.type <> %d)
						AND (m.opgezegd_op > NOW() OR m.opgezegd_op IS NULL)
					GROUP BY
						partition_group',
					$partition_by !== null ? ('l.' . $partition_by) : 'NULL',
					$lijst['id'],
					MEMBER_STATUS_LID_AF));
				break;

			case DataModelMailinglist::TYPE_OPT_OUT:
				$rows = $this->db->query(sprintf('
						SELECT
							u.partition_group,
							SUM(u.cnt) as cnt
						FROM
						(
							SELECT
								COALESCE(%s, -1) as partition_group,
								COUNT(l.id) as cnt
							FROM
								leden l
							LEFT JOIN mailinglijsten_opt_out o ON
								o.mailinglijst_id = %d AND o.lid_id = l.id
							WHERE
								l.type = %d
								AND (o.opgezegd_op > NOW() OR o.opgezegd_op IS NULL)
							GROUP BY
								partition_group
							UNION
							SELECT
								COALESCE(%1$s, -1) as partition_group,
								COUNT(g.abonnement_id)
							FROM
								mailinglijsten_abonnementen g
							LEFT JOIN leden l ON
								l.id = g.lid_id
							WHERE
								g.mailinglijst_id = %2$d
								AND (g.opgezegd_op > NOW() OR g.opgezegd_op IS NULL)
							GROUP BY
								partition_group
						) u
						GROUP BY
							partition_group',
					$partition_by !== null ? ('l.' . $partition_by) : 'NULL',
					$lijst['id'],
					MEMBER_STATUS_LID));
				break;

			default:
				throw new LogicException('Invalid list type');
		}

		// If you didn't particularly partition by anything, there will only be one row :) 
		if ($partition_by === null)
			return (int) $rows[0]['cnt'];

		// Convert the partition-count rows into a dictionary
		$partitions = [];

		foreach ($rows as $row)
			$partitions[$row['partition_group']] = (int) $row['cnt'];

		ksort($partitions);

		return $partitions;
	}

	public function is_subscribed(DataIterMailinglist $list, DataIterMember $member)
	{
		switch ($list['type'])
		{
			case DataModelMailinglist::TYPE_OPT_IN:
				try {
					return $this->get_for_member($list, $member)->is_active();
				} catch (NotFoundException $e) {
					return false;
				}

			case DataModelMailinglist::TYPE_OPT_OUT:
				$count = $this->db->query_value(sprintf('
					SELECT
						COUNT(o.id)
					FROM
						mailinglijsten_opt_out o
					WHERE
						o.mailinglijst_id = %d
						AND o.lid_id = %d
						AND o.opgezegd_op <= NOW()',
					$list['id'], $member['id']));
				return $count === 0;
		}
	}

	public function get_for_member(DataIterMailinglist $list, DataIterMember $member)
	{
		if ($list->get('type') != DataModelMailinglist::TYPE_OPT_IN)
			throw new LogicException('This type of mailing list does not support explicit subscriptions');

		$iter = $this->find_one([
			'mailinglijst_id' => $list['id'],
			'lid_id' => $member['id'],
			new DatabaseLiteral('opgezegd_op IS NULL or opgezegd_op > NOW()')
		]);

		if (!$iter)
			throw new NotFoundException('This member is not subscribed to the mailing list');

		return $iter;
	}

	public function subscribe_guest(DataIterMailinglist $list, $naam, $email)
	{
		return $this->db->insert('mailinglijsten_abonnementen', array(
			'abonnement_id' => sha1(uniqid('', true)),
			'naam' => $naam,
			'email' => $email,
			'mailinglijst_id' => intval($list['id'])
		));
	}

	public function subscribe_member(DataIterMailinglist $list, DataIterMember $member)
	{
		if ($this->is_subscribed($list, $member))
			return;

		switch ($list['type'])
		{
			// Opt in list: add a subscription to the table
			case DataModelMailinglist::TYPE_OPT_IN:
				$this->db->insert('mailinglijsten_abonnementen', array(
					'abonnement_id' => sha1(uniqid('', true)),
					'lid_id' => $member->get_id(),
					'mailinglijst_id' => intval($list['id'])
				));
				break;

			// Opt out list: remove any opt-out entries from the table
			case DataModelMailinglist::TYPE_OPT_OUT:
				$this->db->delete('mailinglijsten_opt_out',
					sprintf('lid_id = %d AND mailinglijst_id = %d',
						$member->get_id(), $list['id']));
				break;

			default:
				throw new RuntimeException('Subscribing to unknown list type not supported');
		}

		get_model('DataModelMailinglist')->send_subscription_mail($list, member_full_name($member, IGNORE_PRIVACY), $member['email']);
	}

	public function unsubscribe_member(DataIter $lijst, DataIterMember $member)
	{
		switch ($lijst->get('type'))
		{
			// For opt-in lists: find the abonnement and delete it.
			case DataModelMailinglist::TYPE_OPT_IN:
				// Find the abonnement id
				$abonnement_id = $this->get_for_member($lijst, $member);
				
				// and unsubscribe using that id
				return $this->cancel_subscription($abonnement_id);

			// For opt-out lists: add an opt-out entry.
			case DataModelMailinglist::TYPE_OPT_OUT:
				$data = array(
					'mailinglijst_id' => intval($lijst['id']),
					'lid_id' => intval($member['id'])
				);

				return $this->db->insert('mailinglijsten_opt_out', $data);
		}
	}

	public function cancel_subscription(DataIterMailinglistSubscription $subscription)
	{
		if ($subscription['opgezegd_op'])
			return;

		if ($this->_is_opt_out_subscription_id($subscription['id']))
			return $this->unsubscribe_member($subscription['mailinglist'], $subscription['lid']);
		
		$subscription['opgezegd_op'] = new DateTime();

		return $this->update($subscription);
	}
	
}