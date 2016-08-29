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
			foreach (get_identity()->member()->get('committees') as $commissie)
				$commissies[$commissie] = $model->get_naam($commissie);

		return $commissies;
	}

	public function preferred_tab(DataIterEditable $editable)
	{
		$language = i18n_get_language();

		$field_map = array(
			'en' => 'content_en',
			'nl' => 'content'
		);

		// Is the preferred field not empty, return that language
		if ($editable->has_field($field_map[$language]) && $editable->get($field_map[$language]) != '')
			return $language;
		
		$alternative = $language == 'en' ? 'nl' : 'en';

		// Otherwise, is the other field not empty, do prefer the alternative
		if ($editable->has_field($field_map[$alternative]) && $editable->get($field_map[$alternative]) != '')
			return $alternative;

		// And if that is also empty, return the preferred language anyway.
		return $language;
	}
}