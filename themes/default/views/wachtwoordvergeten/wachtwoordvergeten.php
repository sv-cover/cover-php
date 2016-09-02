<?php

class WachtwoordvergetenView extends View
{
	public function render_form($success = null, $email = null)
	{
		return $this->render('form.twig', compact('success', 'email'));
	}
}
