<?php
class ActieveledenView extends CRUDView
{
	protected $__file = __FILE__;

	public function get_committees()
	{
		$model = get_model('DataModelCommissie');

		$committees = array();

		foreach ($model->get(true) as $committee)
			$committees[$committee->get_id()] = $committee->get('naam');

		return $committees;
	}

	public function get_functies()
	{
		$model = get_model('DataModelCommissie');

		return array_values(array_flip($model->get_functies()));
	}
}
