<?php

class CommitteeBattleView extends CRUDView {
	protected $__file = __FILE__;

	public $committee_view;

	public function __construct(Controller $controller = null)
	{
		parent::__construct($controller);

		$this->committee_view = View::byName('commissies', $controller);
	}

	public function committee_options()
	{
		$model = get_model('DataModelCommissie');
		$model->type = DataModelCommissie::TYPE_COMMITTEE;

		$committees = $model->get(false);

		return array_combine(
			array_map(getter('id'), $committees),
			array_map(getter('naam'), $committees)
		);
	}
}