<?php

require_once 'include/member.php';

class PolicyFotoboek implements Policy
{
	public function user_can_create()
	{
		return member_in_commissie(COMMISSIE_FOTOCIE);
	}

	public function user_can_read(DataIter $book)
	{
		// Members can see everything
		if (logged_in())
			return true;

		// Chantagemap is not visible for non-members
		if (strcasecmp($book->get('titel'), "Chantagemap") == 0)
			return false;

		// Download photo's is not visible for non-members
		if (strcasecmp($book->get('titel'), "Download grote foto's") == 0)
			return false;

		// Older photo books are not visible for non-members
		if (preg_match("/^Foto's uit ([\d]{4})\/[\d]{4}$/i", $book->get('titel'), $matches))
			return $matches[1] == date("Y") || $matches[1] === date("Y", strtotime("-1 year"));

		// Member-specific albums are also forbidden terrain
		if ($book instanceof DataIterFacesPhotobook)
			return false;

		return true;
	}

	public function user_can_update(DataIter $book)
	{
		return member_in_commissie(COMMISSIE_FOTOCIE)
			&& ctype_digit($book->get_id())
			&& $book->get_id() > 0;
	}

	public function user_can_delete(DataIter $book)
	{
		return $this->user_can_update($book);
	}
}
