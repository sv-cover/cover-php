<?php

require_once 'src/framework/auth.php';
require_once 'src/models/DataModelVacancy.php';

class PolicyVacancy implements Policy
{
	public function user_can_create(DataIter $vacancy)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_COMEXA);
	}

	public function user_can_read(DataIter $vacancy)
	{
		return True;
	}

	public function user_can_update(DataIter $vacancy)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_COMEXA);
	}

	public function user_can_delete(DataIter $vacancy)
	{
		return $this->user_can_update($vacancy);
	}
}
