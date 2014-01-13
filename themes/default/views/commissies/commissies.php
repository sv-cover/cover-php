<?php
require_once('editable.php');

class CommissiesView extends View
{
	protected $__file = __FILE__;

	public function get_commissie_thumb($commissie)
	{
		$search_paths = array(
			'images/' . $commissie->get('login') . 'tn.gif',
			'images/' . $commissie->get('login') . 'tn.png',
			'images/' . $commissie->get('login') . 'tn.jpg');

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
