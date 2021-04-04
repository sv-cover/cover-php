<?php

require_once 'src/framework/member.php';

class PolicySticker implements Policy
{
	public function user_can_create(DataIter $sticker)
	{
		// Members are allowed to add new stickers (also contributors etc.)
		return get_auth()->logged_in();
	}

	public function user_can_read(DataIter $sticker)
	{
		return true;
	}

	public function user_can_update(DataIter $sticker)
	{
		// Board can admin the stickers
		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR))
			return true;

		// Only the owner can update their stickers
		if ($sticker->get('toegevoegd_door') != null)
			return $sticker->get('toegevoegd_door') == get_identity()->get('id');

		return false;
	}

	public function user_can_delete(DataIter $sticker)
	{
		return $this->user_can_update($sticker);
	}
}
