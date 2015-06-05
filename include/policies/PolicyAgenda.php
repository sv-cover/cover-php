<?php

require_once 'include/member.php';

class PolicyAgenda implements Policy
{
	public function user_can_create()
	{
		return get_auth()->member_in_committee();
	}

	public function user_can_read(DataIter $agenda_item)
	{
		if ($agenda_item->is_proposal())
			return member_in_commissie(COMMISSIE_BESTUUR)
				|| member_in_commissie(COMMISSIE_KANDIBESTUUR)
				|| member_in_commissie($agenda_item->get('commissie'));

		elseif ($agenda_item->get('private'))
			return (bool) logged_in();

		else
			return true;
	}

	public function user_can_update(DataIter $agenda_item)
	{
		if ($agenda_item->is_proposal())
			return false;

		if (member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie(COMMISSIE_KANDIBESTUUR))
			return true;

		elseif (member_in_commissie($agenda_item->get('commissie')))
			return true;

		else
			return false;
	}

	public function user_can_delete(DataIter $agenda_item)
	{
		return $this->user_can_update($agenda_item);
	}

	public function user_can_moderate(DataIter $agenda_item)
	{
		return $agenda_item->is_proposal() &&
			(member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie(COMMISSIE_KANDIBESTUUR));
	}
}
