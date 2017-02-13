<?php

class ConfirmView extends View
{
	public function render_password_reset($member)
	{
		return $this->render('password_reset.twig', compact('member'));
	}

	public function render_email_confirmed($member)
	{
		return $this->render('email_confirmed.twig', compact('member'));
	}

	public function render_invalid_key()
	{
		return $this->render('invalid_key.twig');
	}
}