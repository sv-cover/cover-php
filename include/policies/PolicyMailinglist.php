<?php

class PolicyMailinglist implements Policy
{
	public function user_can_create(DataIter $iter)
	{
		return true;
	}

	public function user_can_read(DataIter $iter)
	{
		return true;
	}

	public function user_can_update(DataIter $iter)
	{
		return true;
	}

	public function user_can_delete(DataIter $iter)
	{
		return true;
	}
} 