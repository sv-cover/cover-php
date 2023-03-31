<?php

require_once 'src/framework/auth.php';
require_once 'src/models/DataModelPartner.php';

class PolicyPartner implements Policy
{
	public function user_can_create(DataIter $partner)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_COMEXA);
	}

	public function user_can_read(DataIter $partner)
	{
		return True;
	}

	public function user_can_update(DataIter $partner)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_COMEXA);
	}

	public function user_can_delete(DataIter $partner)
	{
		return $this->user_can_update($partner);
	}
}
