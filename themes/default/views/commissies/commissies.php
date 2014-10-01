<?php
require_once 'editable.php';

class CommissiesView extends CRUDView
{
	protected $__file = __FILE__;

	public function get_commissie_thumb($commissie)
	{
		return $this->find_image($commissie->get('login') . 'tn');
	}

	public function get_commissie_photo($commissie)
	{
		return $this->find_image($commissie->get('login'));
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

	public function get_summary($commissie)
	{
		/* Get the first editable page */
		$editable_model = get_model('DataModelEditable');
		$page = $editable_model->get_iter($commissie->get('page'));

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
