<?php

require_once 'include/auth.php';
require_once 'include/models/DataModelAnnouncement.php';

class PolicyAnnouncement implements Policy
{
	public function user_can_create(DataIter $announcement)
	{
		return get_identity()->member_in_committee();
	}

	public function user_can_read(DataIter $announcement)
	{
		switch ($announcement->get('visibility'))
		{
			case DataModelAnnouncement::VISIBILITY_PUBLIC:
				return true;

			case DataModelAnnouncement::VISIBILITY_MEMBERS:
				return get_identity()->member_is_active();

			case DataModelAnnouncement::VISIBILITY_ACTIVE_MEMBERS:
				return get_identity()->member_in_committee();

			default:
				return false;
		}
	}

	public function user_can_update(DataIter $announcement)
	{
		return get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee($announcement->get('committee'));
	}

	public function user_can_delete(DataIter $announcement)
	{
		return $this->user_can_update($announcement);
	}
}
