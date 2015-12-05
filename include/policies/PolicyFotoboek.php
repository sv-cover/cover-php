<?php

require_once 'include/member.php';

class PolicyFotoboek implements Policy
{
	public function user_can_create()
	{
		return get_identity()->member_in_committee(COMMISSIE_FOTOCIE);
	}

	public function user_can_read(DataIter $book)
	{
		if (!$book instanceof DataIterPhotobook)
			throw new RuntimeException('$book not an instance of DataIterPhotobook');
		
		// First: if the access to the photo book is of a higher level
		// than the current user has, no way he/she can view the photo
		// book.
		if ($book->has('visibility') && $this->get_access_level() < $book->get('visibility'))
			return false;

		// Member-specific albums are also forbidden terrain unless they are about you
		if (!get_identity()->member_is_active() && $book instanceof DataIterFacesPhotobook)
			return $book['member_ids'] == [get_identity()->get('id')];

		// Older photo books are not visible for non-members
		if (!get_identity()->member_is_active() && $book->has('date') && preg_match('/^(\d{4})-\d{1,2}-\d{1,2}$/', $book->get('date'), $match))
			return intval($match[1]) >= intval(date("Y", strtotime("-2 year")));

		return true;
	}

	public function user_can_update(DataIter $book)
	{
		if (!$book instanceof DataIterPhotobook)
			throw new RuntimeException('$book not an instance of DataIterPhotobook');
		
		return get_identity()->member_in_committee(COMMISSIE_FOTOCIE)
			&& ctype_digit((string) $book->get_id())
			&& $book->get_id() > 0;
	}

	public function user_can_delete(DataIter $book)
	{
		if (!$book instanceof DataIterPhotobook)
			throw new RuntimeException('$book not an instance of DataIterPhotobook');
		
		return $this->user_can_update($book);
	}

	public function get_access_level()
	{
		if (get_identity()->member_in_committee(COMMISSIE_FOTOCIE))
			return DataModelFotoboek::VISIBILITY_PHOTOCEE;

		if (get_identity()->member_in_committee())
			return DataModelFotoboek::VISIBILITY_ACTIVE_MEMBERS;

		if (get_identity()->member_is_active())
			return DataModelFotoboek::VISIBILITY_MEMBERS;

		else
			return DataModelFotoboek::VISIBILITY_PUBLIC;
	}
}
