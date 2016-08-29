<?php
	
class WeblogView extends View
{
	public function render_index($iters)
	{
		return $this->render('index.twig', compact('iters'));
	}

	public function stylesheets()
	{
		return array_merge(
			parent::stylesheets(),
			[get_theme_data('styles/weblog.css')]);
	}

	public function weblog_head($iter)
	{
		if ($iter['author_type'] != 1)
			return 'images/heads/none.png';

		if (file_exists('images/heads/' . $iter['author'] . '.png'))
			return 'images/heads/' . $iter['author'] . '.png';
		else
			return 'foto.php?lid_id=' . $iter['author'] . '&format=square&width=200';
	}	
}
