<?php

require_once 'src/framework/member.php';

class PolicyPhoto implements Policy
{
	public function user_can_create(DataIter $photo)
	{
		return get_policy($photo['scope'])->user_can_update($photo['scope']);
	}

	public function user_can_read(DataIter $photo)
	{
		return get_policy($photo['scope'])->user_can_read($photo['scope']);
	}

	public function user_can_update(DataIter $photo)
	{
		return get_policy($photo['scope'])->user_can_update($photo['scope']);
	}

	public function user_can_delete(DataIter $photo)
	{
		return get_policy($photo['scope'])->user_can_update($photo['scope']);
	}
}
