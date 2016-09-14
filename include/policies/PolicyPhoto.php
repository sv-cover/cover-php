<?php

require_once 'include/member.php';

class PolicyPhoto implements Policy
{
	public function user_can_create(DataIter $photo)
	{
		return get_policy($photo['book'])->user_can_update($photo['book']);
	}

	public function user_can_read(DataIter $photo)
	{
		return get_policy($photo['book'])->user_can_read($photo['book']);
	}

	public function user_can_update(DataIter $photo)
	{
		return get_policy($photo['book'])->user_can_update($photo['book']);
	}

	public function user_can_delete(DataIter $photo)
	{
		return get_policy($photo['book'])->user_can_update($photo['book']);
	}
}
