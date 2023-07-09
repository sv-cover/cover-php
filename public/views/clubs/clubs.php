<?php

class ClubsView extends View
{
	public function render_form($data, $errors)
	{
		return $this->twig->render('form.twig', compact('data', 'errors'));
	}
}
