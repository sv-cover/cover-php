<?php

require_once 'include/member.php';

class PolicyFotoboekReacties implements Policy
{
	public function user_can_create()
	{
		return logged_in();
	}

	public function user_can_read(DataIter $reactie)
	{
		return true;
	}

	public function user_can_update(DataIter $reactie)
	{
		return member_in_commissie(COMMISSIE_FOTOCIE)
			|| logged_in() && logged_in('id') == $reactie->get('author');
	}

	public function user_can_delete(DataIter $reactie)
	{
		return member_in_commissie(COMMISSIE_FOTOCIE)
			|| logged_in() && logged_in('id') == $reactie->get('author');;
	}
}
