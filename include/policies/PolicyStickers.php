<?php

require_once 'include/member.php';

class PolicyStickers implements Policy
{
	public function user_can_create()
	{
		return get_auth()->logged_in();
	}

	public function user_can_read(DataIter $sticker)
	{
		return true;
	}

	public function user_can_update(DataIter $sticker)
	{
		return member_in_commissie(COMMISSIE_BESTUUR)
			|| ($sticker->get('toegevoegd_door') != null && $sticker->get('toegevoegd_door') == logged_in('id'));
	}

	public function user_can_delete(DataIter $sticker)
	{
		return $this->user_can_update($sticker);
	}
}
