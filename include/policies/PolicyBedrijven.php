<?php

require_once 'include/member.php';

class PolicyBedrijven implements Policy
{
	public function user_can_create()
	{
		return member_in_commissie(COMMISSIE_BESTUUR)
			|| member_in_commissie(COMMISSIE_PRCIE);
	}

	public function user_can_read(DataIter $bedrijf)
	{
		return true;
	}

	public function user_can_update(DataIter $bedrijf)
	{
		return $this->user_can_create();
	}

	public function user_can_delete(DataIter $bedrijf)
	{
		return $this->user_can_update($bedrijf);
	}
}
