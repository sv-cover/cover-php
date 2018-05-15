<?php

class SignUpView extends View
{
	protected $__file = __FILE__;

	public function available_committees()
	{
		$committees = array();

		$model = get_model('DataModelCommissie');

		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) || get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			foreach ($model->get(null, true) as $commissie)
				$committees[$commissie->get_id()] = $commissie->get('naam');
		else
			foreach (get_identity()->member()->get('committees') as $commissie)
				$committees[$commissie] = $model->get_naam($commissie);

		return $committees;
	}
}