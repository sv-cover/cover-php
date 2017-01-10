<?php

class CommitteeBattleView extends CRUDView {
	protected $__file = __FILE__;

	public $committee_view;

	public function __construct(Controller $controller = null)
	{
		parent::__construct($controller);

		$this->committee_view = View::byName('commissies', $controller);
	}

	public function get_scripts()
	{
		return array_merge(parent::get_scripts(), [
			get_theme_data('data/select2.min.js', false)
		]);
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
}