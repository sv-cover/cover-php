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

	public function get_author_link(DataIter $message, $last = false)
	{
		if ($last && $message['last_author_type'])
			$field = 'last_author';
		else
			$field = 'author';

		try {
			switch (intval($message[$field . '_type']))
			{
				case DataModelForum::TYPE_PERSON: /* Person */
					return 'profiel.php?lid=' . $message[$field];
				
				case DataModelForum::TYPE_COMMITTEE: /* Commissie */
					$committee_model = get_model('DataModelCommissie');
					$committee = $committee_model->get_iter($message[$field]);
					return 'commissies.php?commissie=' . urlencode($committee['login']);
				
				default:
					return null;
			}
		} catch (DataIterNotFoundException $e) {
			// Sometimes an author just doesnt exist anymore in the database. That's legacy for you!
			return null;
		}
	}
}
