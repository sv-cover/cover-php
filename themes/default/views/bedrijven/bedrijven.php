<?php
require_once 'editable.php';

class BedrijvenView extends CRUDView
{
	protected $__file = __FILE__;

	public function get_bedrijf_thumb(DataIter $bedrijf)
	{
		return null;
	}

	public function get_summary($bedrijf)
	{
		/* Get the first editable page */
		$editable_model = get_model('DataModelEditable');
		$page = $editable_model->get_iter($bedrijf->get('page'));

		if (!$page)
			return '';

		$language = i18n_get_language();

		$property = isset($page->data['content_' . $language])
			? 'content_' . $language
			: 'content';

		$content = $page->get($property);
		
		return editable_get_summary($content, $page->get('owner'));
	}
}
