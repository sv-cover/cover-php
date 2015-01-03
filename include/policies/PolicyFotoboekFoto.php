<?php

require_once 'include/member.php';

class PolicyFotoboekFoto implements Policy
{
	public function user_can_create()
	{
		return member_in_commissie(COMMISSIE_FOTOCIE);
	}

	public function user_can_read(DataIter $photo)
	{
		return get_policy('DataModelFotoboek')->user_can_read($photo->book);
	}

	public function user_can_update(DataIter $photo)
	{
		return get_policy('DataModelFotoboek')->user_can_update($photo->book);
	}

	public function user_can_delete(DataIter $photo)
	{
		if ($photo->book instanceof DataIterFacesPhotobook)
			return $photo->book->get('member_id') == logged_in('id');

		if ($photo->book instanceof DataIterLikedPhotobook)
			return true;

		return get_policy('DataModelFotoboek')->user_can_update($photo->book);
	}
}
