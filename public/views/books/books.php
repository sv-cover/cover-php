<?php

class BooksView extends View
{
	public function render_call_to_action($webshop_link)
	{
		return $this->twig->render('go_to_webshop.twig', compact('webshop_link'));
	}

	public function render_call_to_log_in()
	{
		return $this->twig->render('go_to_log_in.twig');
	}
}
