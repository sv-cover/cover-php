<?php

class HomepageView extends View
{
	public function render_homepage()
	{
		return $this->twig->render('homepage.twig');
	}
}
