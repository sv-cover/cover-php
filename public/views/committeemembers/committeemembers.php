<?php

class CommitteemembersView extends View 
{
	public function render_index($iters)
	{
		return $this->twig->render('index.twig', compact('iters'));
	}
}
