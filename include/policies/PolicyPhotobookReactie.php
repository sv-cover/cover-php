<?php

require_once 'include/member.php';

class PolicyPhotobookReactie implements Policy
{
	public function user_can_create(DataIter $reactie)
	{
		return get_auth()->logged_in();
	}

	public function user_can_read(DataIter $reactie)
	{
		return true;
	}

	public function user_can_update(DataIter $reactie)
	{
		// PhotoCee and the authors of comments are the only one who can clean/update and delete comments.
		
		return member_in_commissie(COMMISSIE_FOTOCIE)
			|| get_auth()->logged_in() && get_identity()->get('id') == $reactie->get('auteur');
	}

	public function user_can_delete(DataIter $reactie)
	{
		return member_in_commissie(COMMISSIE_FOTOCIE)
			|| get_auth()->logged_in() && get_identity()->get('id') == $reactie->get('auteur');;
	}
}
