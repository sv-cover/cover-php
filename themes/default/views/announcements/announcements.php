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

		$committees = array_map([$model, 'get_iter'], get_identity()->get('committees'));

		$pairs = array();

		foreach ($committees as $committee)
			$pairs[$committee->get_id()] = $committee->get('naam');

		// Add the current committee as option if it isn't already (for editing)
		if ($iter && $iter->has('committee') && !isset($pairs[$iter->get('committee')]))
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
