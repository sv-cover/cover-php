<?php
require_once('editable.php');

class BedrijvenView extends View
{
	protected $__file = __FILE__;

	public function get_bedrijf_thumb($bedrijf)
	{
		return $this->find_image($bedrijf->get('login') . 'tn');
	}

	public function get_bedrijf_photo($bedrijf)
	{
		return $this->find_image($bedrijf->get('login'));
	}

	private function find_image($basename)
	{
		$search_paths = array(
			'images/' . $basename . '.gif', // Brainstorm
			'images/' . $basename . '.jpg', // Small photo
			'images/' . $basename . '.png'	// Committee logo
		);

		foreach ($search_paths as $path)
			if (file_exists($path))
				return $path;

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
