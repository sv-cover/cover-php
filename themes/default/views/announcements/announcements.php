<?php
require_once 'include/markup.php';
require_once 'include/form.php';
require_once 'include/models/DataModelAnnouncement.php';

class AnnouncementsView extends CRUDView
{
	public function get_committee_options(DataIter $iter = null)
	{
		$model = get_model('DataModelCommissie');

		$committees = array_map([$model, 'get_iter'], get_identity()->get('committees'));

		$pairs = array();

		foreach ($committees as $committee)
			$pairs[$committee->get_id()] = $committee->get('naam');

		// Add the current committee as option if it isn't already (for editing)
		if ($iter && !empty($iter['committee_id']) && !isset($pairs[$iter->get('committee_id')]))
			$pairs[$iter->get('committee_id')] = $iter->get('committee')->get('naam');

		return $pairs;
	}

	public function get_visibility_options()
	{
		return array(
			DataModelAnnouncement::VISIBILITY_PUBLIC => __('Everyone'),
			DataModelAnnouncement::VISIBILITY_MEMBERS => __('Only logged in members'),
			DataModelAnnouncement::VISIBILITY_ACTIVE_MEMBERS => __('Only logged in active members')
		);
	}
}
