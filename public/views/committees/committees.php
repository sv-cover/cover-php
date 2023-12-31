<?php

class CommitteesView extends CRUDView
{
	protected $__file = __FILE__;

	public function get_summary(DataIterCommissie $commissie)
	{
		return $commissie['page'] ? $commissie['page']['summary'] : '';
	}

	public function get_activities(DataIterCommissie $iter)
	{
		$model = get_model('DataModelAgenda');
		$activiteiten = array();

		foreach ($model->get_agendapunten() as $punt)
			if ($punt['committee_id'] == $iter['id'] && get_policy($model)->user_can_read($punt))
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
		return $model->get_lid_for_functie(COMMISSIE_BESTUUR, 'commissioner of internal affairs');
	}

	public function render_archive($iters)
	{
		return $this->twig->render('archive.twig', compact('iters'));
	}

	public function render_slide(DataIterCommissie $committee)
	{
		return $this->render('slide.twig', compact('committee'));
	}

	public function available_committee_types()
	{
		return [
			DataModelCommissie::TYPE_COMMITTEE => __('committee'),
			DataModelCommissie::TYPE_WORKING_GROUP => __('working group'),
			DataModelCommissie::TYPE_OTHER => __('other')
		];
	}
}
