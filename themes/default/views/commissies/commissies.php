<?php
require_once 'include/editable.php';

class CommissiesView extends CRUDView
{
	protected $__file = __FILE__;

	public function get_commissie_thumb($commissie)
	{
		return $this->find_image(array(
			'images/committees/' . $commissie->get('login') . 'tn.gif',
			'images/committees/' . $commissie->get('login') . 'tn.jpg',
			'images/committees/logos/' . $commissie->get('login') . '.png'
		));
	}

	public function get_commissie_photo($commissie)
	{
		return $this->find_image(array(
			'images/committees/' . $commissie->get('login') . '.gif',
			'images/committees/' . $commissie->get('login') . '.jpg'
		));
	}

	private function find_image($search_paths)
	{
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
