<?php

class CommitteeBattleView extends CRUDView
{
	public function scripts()
	{
		return array_merge(parent::scripts(), [
			get_theme_data('data/select2.min.js', false)
		]);
	}

	public function stylesheets()
	{
		return array_merge(parent::stylesheets(), [
			get_theme_data('styles/select2.css')
		]);
	}

	public function committee_view()
	{
		return View::byName('commissies', $this->controller);
	}

	public function committee_options()
	{
		$model = get_model('DataModelCommissie');
		$model->type = DataModelCommissie::TYPE_COMMITTEE;

		return $model->get(false);
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

	public function render_committee(DataIterCommissie $iter, $scores, DataModelCommissie $committee_model)
	{
		return $this->render('committee.twig', compact('iter', 'scores', 'committee_model'));
	}
}