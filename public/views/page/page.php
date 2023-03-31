<?php

class PageView extends CRUDFormView
{
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