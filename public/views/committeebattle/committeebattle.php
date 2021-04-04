<?php

class CommitteeBattleView extends CRUDView
{
	public function committee_view()
	{
		return View::byName('committees', $this->controller);
	}

	public function committee_options()
	{
		$model = get_model('DataModelCommissie');
		return $model->get(DataModelCommissie::TYPE_COMMITTEE);
	}

	public function active_member_options()
	{
		$model = get_model('DataModelActieveLeden');
		$active_members = $model->get_active_members(DataModelCommissie::TYPE_COMMITTEE, false);

		return array_combine(
			array_map(getter('id'), $active_members),
			array_map('member_full_name', $active_members)
		);
	}

	public function render_committee(DataIterCommissie $iter, array $scores, DataModelCommissie $committee_model)
	{
		$committees = $committee_model->get(DataModelCommissie::TYPE_COMMITTEE);
		
		return $this->render('committee.twig', compact('iter', 'scores', 'committee_model', 'committees'));
	}
}