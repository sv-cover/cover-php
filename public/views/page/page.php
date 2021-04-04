<?php

class PageView extends CRUDView
{
	public function available_committees(DataIterEditable $iter)
	{
		$options = array(
			'member' => [],
			'all' => []
		);

		$model = get_model('DataModelCommissie');

		// At least populate my list of committees
		foreach (get_identity()->member()->get('committees') as $commissie)
			$options['member'][$commissie] = $model->get_naam($commissie);

		// And if I am very important, also populate the all list. That there are doubles is not a problem.
		if ($this->controller->can_set_committee_id($iter))
			foreach ($model->get(null, true) as $commissie)
				$options['all'][$commissie->get_id()] = $commissie->get('naam');
		
		// Empty groups will be pruned anyway
		return [
			__('Your committees') => $options['member'],
			__('All committees') => $options['all']
		];
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

	public function render_preview(DataIterEditable $editable, $lang = null)
	{
		$language = i18n_get_language();

		$field_map = array(
			'en' => 'content_en',
			'nl' => 'content'
		);

		if ($lang !== null && array_key_exists($lang, $field_map))
			$language = $lang;
		
		return markup_parse($editable[$field_map[$language]]);
	}
}