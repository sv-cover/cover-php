<?php

class ShowView extends CRUDView
{
	public function render_preview(DataIterEditable $editable)
	{
		return editable_parse($editable->get_content(), null);
	}

	public function available_committees()
	{
		$commissies = array();

		$model = get_model('DataModelCommissie');

		if (member_in_commissie(COMMISSIE_BESTUUR) || member_in_commissie(COMMISSIE_KANDIBESTUUR))
			foreach ($model->get() as $commissie)
				$commissies[$commissie->get_id()] = $commissie->get('naam');
		else
			foreach (get_identity()->get_member()->get('committees') as $commissie)
				$commissies[$commissie] = $model->get_naam($commissie);

		return $commissies;
	}
}