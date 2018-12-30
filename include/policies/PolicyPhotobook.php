<?php

require_once 'include/member.php';

class PolicyPhotobook implements Policy
{
	private function _was_member_at_the_time(DataIter $book) {
		if (get_identity()->member() === null)
			return false;

		if ($book['date'] === null)
			return false;

		if (!preg_match('/^(?P<year>\d{4})-\d{1,2}-\d{1,2}$/', $book['date'], $match))
			return false;

		return get_identity()->member()->is_member_on(new DateTime($match[0]));
	}

	private function _inside_public_period(DataIter $book)
	{
		if ($book['date'] === null)
			return false;

		if (!preg_match('/^(?P<year>\d{4})-\d{1,2}-\d{1,2}$/', $book['date'], $match))
			return false;

		return intval($match['year']) >= intval(date("Y", strtotime("-2 year")));
	}

	public function user_can_create(DataIter $book)
	{
		return get_identity()->member_in_committee(COMMISSIE_FOTOCIE)
			&& ctype_digit((string) $book['parent_id']); // no generated photobook
	}

	public function user_can_read(DataIter $book)
	{
		// First: if the access to the photo book is of a higher level
		// than the current user has, no way he/she can view the photo
		// book.
		if ($book['visibility'] !== null && $this->get_access_level() < $book->get('visibility'))
			return false;

		// Member-specific albums are also forbidden terrain unless they are about you
		if (!get_identity()->is_member() && $book instanceof DataIterFacesPhotobook)
			return $book['member_ids'] == [get_identity()->get('id')];

		// Member-specific albums are forbidden if one of the members has marked their photo* as hidden
		if ($book instanceof DataIterFacesPhotobook && !get_identity()->member_in_committee(COMMISSIE_BESTUUR))
			foreach ($book['members'] as $member)
				if ($member->is_private('foto'))
					return false;

		// Older photo books are not visible for non-members
		if (get_identity()->is_member())
			return true;

		if ($book['date'] === null)
			return true;

		if ($this->_was_member_at_the_time($book))
			return true;

		if ($this->_inside_public_period($book))
			return true;

		return false;
	}

	public function user_can_update(DataIter $book)
	{
		return get_identity()->member_in_committee(COMMISSIE_FOTOCIE)
			&& ctype_digit((string) $book->get_id()) // test whether this isn't a special book, such as the Favorites or Faces albums which are generated
			&& $book->get_id() > 0;
	}

	public function user_can_delete(DataIter $book)
	{
		return $this->user_can_update($book);
	}

	public function user_can_download_book(DataIterPhotobook $book)
	{
		if ($book instanceof DataIterRootPhotobook)
			return false;

		if (!get_identity()->member())
			return false;

		if (get_identity()->is_member() || ($book['date'] && get_identity()->member()->is_member_on(new DateTime($book['date']))))
			return $this->user_can_read($book);

		return false;
	}

	public function user_can_mark_as_read(DataIterPhotobook $book)
	{
		return // only logged in members can track their viewed photo books
			get_auth()->logged_in() 
			
			// and only if enabled
			&& get_config_value('enable_photos_read_status', true) 

			// and only if we actually are watching a book
			&& $book->get_id() 
			
			// which is not artificial (faces, likes) and has photos
			&& ctype_digit($book->get_id()) && $book['num_books'] > 0;
	}

	public function get_access_level()
	{
		if (get_identity()->member_in_committee(COMMISSIE_FOTOCIE))
			return DataModelPhotobook::VISIBILITY_PHOTOCEE;

		if (get_identity()->member_in_committee())
			return DataModelPhotobook::VISIBILITY_ACTIVE_MEMBERS;

		if (get_identity()->is_member())
			return DataModelPhotobook::VISIBILITY_MEMBERS;

		else // Donors are also treated as PUBLIC
			return DataModelPhotobook::VISIBILITY_PUBLIC;
	}
}
