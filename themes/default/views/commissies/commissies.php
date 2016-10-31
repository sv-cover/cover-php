<?php

class CommissiesView extends CRUDView
{
	protected $__file = __FILE__;

	public function stylesheets()
	{
		return array_merge(
			parent::stylesheets(),
			[get_theme_data('styles/commissies.css')]);
	}

	public function get_commissie_thumb(DataIterCommissie $commissie)
	{
		return $this->find_image(array(
			'images/committees/' . $commissie->get('login') . 'tn.gif',
			'images/committees/' . $commissie->get('login') . 'tn.jpg',
			'images/committees/logos/' . $commissie->get('login') . '.png'
		));
	}

	public function get_commissie_photo(DataIterCommissie $commissie)
	{
		$path = $this->find_image(array(
			'images/committees/' . $commissie->get('login') . '.gif',
			'images/committees/' . $commissie->get('login') . '.jpg'
		));

		if (!$path)
			return null;

		list($width, $height) = getimagesize($path);
		$orientation = $height > $width ? 'vertical' : 'horizontal';

		return [
			'orientation' => $orientation,
			'url' => $path
		];
	}

	private function find_image($search_paths)
	{
		foreach ($search_paths as $path)
			if (file_exists($path))
				return $path;

		return null;
	}

	public function get_summary(DataIterCommissie $commissie)
	{
		/* Get the first editable page */
		$editable_model = get_model('DataModelEditable');
		$page = $editable_model->get_iter($commissie->get('page'));

		if (!$page)
			return '';

		return $page->get_summary();
	}

	public function get_activities(DataIterCommissie $iter)
	{
		$model = get_model('DataModelAgenda');
		$activiteiten = array();

		foreach ($model->get_agendapunten() as $punt)
			if ($punt->get('commissie') == $iter->get('id') && get_policy($model)->user_can_read($punt))
				$activiteiten[] = $punt;

		return $activiteiten;
	}

	public function get_navigation(DataIterCommissie $iter)
	{
		$model = $this->controller->model();

		$committees = $model->get(false);

		$committees = array_filter($committees, [get_policy($model), 'user_can_read']);

		$current_index = array_usearch($iter, $committees,
			function($a, $b) { return $a->get_id() == $b->get_id(); });

		$nav = new stdClass();

		$nav->previous = $current_index !== null && $current_index > 0
			? $committees[$current_index - 1]
			: null;

		$nav->next = $current_index !== null && $current_index < count($committees) - 1
			? $committees[$current_index + 1]
			: null;

		return $nav;
	}

	public function commissioner_of_internal_affairs()
	{
		$model = get_model('DataModelCommissie');
		return $model->get_lid_for_functie(COMMISSIE_BESTUUR, 'commissaris intern');
	}

	public function render_working_groups($iters)
	{
		return $this->twig->render('working_groups.twig', compact('iters'));
	}

	public function available_committee_types()
	{
		return [
			DataModelCommissie::TYPE_COMMITTEE => __('Commissie'),
			DataModelCommissie::TYPE_WORKING_GROUP => __('Werkgroep')
		];
	}
}
