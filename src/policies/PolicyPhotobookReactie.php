<?php

require_once 'src/framework/member.php';

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
		
		return get_identity()->member_in_committee(COMMISSIE_FOTOCIE)
			|| get_auth()->logged_in() && get_identity()->get('id') == $reactie->get('auteur');
	}

	public function user_can_delete(DataIter $reactie)
	{
		return $this->user_can_update($reactie);
	}
}
