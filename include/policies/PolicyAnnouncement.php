<?php

require_once 'include/member.php';
require_once 'include/models/DataModelAnnouncement.php';

class PolicyAnnouncement implements Policy
{
	public function user_can_create()
	{
		return logged_in() && count(logged_in('commissies')) > 0;
	}

	public function user_can_read(DataIter $announcement)
	{
		switch ($announcement->get('visibility'))
		{
			case DataModelAnnouncement::VISIBILITY_PUBLIC:
				return true;

			case DataModelAnnouncement::VISIBILITY_MEMBERS:
				return logged_in();

			case DataModelAnnouncement::VISIBILITY_ACTIVE_MEMBERS:
				return logged_in() && count(logged_in('commissies')) > 0;

			default:
				return false;
		}
	}

	public function user_can_update(DataIter $announcement)
	{
		return member_in_commissie($announcement->get('committee'));
	}

	public function user_can_delete(DataIter $announcement)
	{
		return $this->user_can_update($announcement);
	}
}
