<?php

require_once 'include/member.php';

class PolicyFotoboekFaces implements Policy
{
	public function user_can_create()
	{
		return get_auth()->logged_in();
	}

	public function user_can_read(DataIter $face)
	{
		return get_auth()->logged_in();
	}

	public function user_can_update(DataIter $face)
	{
		return get_auth()->logged_in();
	}

	public function user_can_delete(DataIter $face)
	{
		return get_auth()->logged_in();
	}
}
