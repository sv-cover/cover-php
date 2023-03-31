<?php

class PolicyWiki implements Policy
{
	public function user_can_create(DataIter $wiki)
	{
		return false;
	}

	public function user_can_read(DataIter $wiki)
	{
		return true;
	}

	public function user_can_update(DataIter $wiki)
	{
		return false;
	}

	public function user_can_delete(DataIter $wiki)
	{
		return false;
	}
}
