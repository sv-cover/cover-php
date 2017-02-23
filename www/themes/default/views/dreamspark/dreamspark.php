<?php

class DreamsparkView extends View
{
	public function render_accept()
	{
		return $this->twig->render('accept.twig');
	}
}
