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

	public function get_committee_battle_banner_photos($iter)
	{
		$committee_photos = array_map(getter('thumbnail'), $iter);
		return array_values(array_filter($committee_photos));
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

	public function get_navigation(array $committees, DataIterCommissie $iter)
	{
		$committees = array_filter($committees, [get_policy('DataModelCommissie'), 'user_can_read']);

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
