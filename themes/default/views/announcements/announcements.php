<?php
require_once 'include/markup.php';
require_once 'include/form.php';
require_once 'include/models/DataModelAnnouncement.php';

class AnnouncementsView extends CRUDView
{
	protected $__file = __FILE__;

	protected function get_committee_options(DataIter $iter = null)
	{
		$model = get_model('DataModelCommissie');

		$committees = $model->get_commissies_for_member(logged_in('id'));

		$pairs = array();

		foreach ($committees as $committee)
			$pairs[$committee->get_id()] = $committee->get('naam');

		if ($iter
			&& $iter->has('committee')
			&& !isset($pairs[$iter->get('committee')])
			&& member_in_commissie(COMMISSIE_EASY))
			$pairs[$iter->get('committee')] = $iter->getIter('committee')->get('naam');

		return $pairs;
	}

	protected function get_visibility_options()
	{
		return array(
			DataModelAnnouncement::VISIBILITY_PUBLIC => __('Iedereen'),
			DataModelAnnouncement::VISIBILITY_MEMBERS => __('Alleen ingelogde leden'),
			DataModelAnnouncement::VISIBILITY_ACTIVE_MEMBERS => __('Alleen ingelogde actieve leden')
		);
	}
}
