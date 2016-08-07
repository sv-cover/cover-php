<?php
require_once 'include/editable.php';
require_once 'include/form.php';
require_once 'include/markup.php';
require_once 'include/member.php';

class EditableView extends View
{
	/**
	 * Same as View::redirect, except that it can be called while outputting stuff,
	 * and it will call exit, because ControllerEditable is often embedded in the
	 * layout of stuff.
	 */
	public function redirect($url, $permanent = false, $flags = 0)
	{
		// Terminate all output
		while (ob_get_level() > 0 && ob_end_clean());

		// Stop doing stuff after the redirect
		parent::redirect($url, $permanent, $flags);
		exit;
	}

	public function render_editable(DataIterEditable $iter, $params = null)
	{
		// Remove unnecessary breaks from the beginning of the page.
		return preg_replace('/^(\<br\/?\>\s*)+/i', '', $params['page']);
	}

	public function render_edit(DataIterEditable $iter, $language)
	{
		if (!in_array('content_' . $language, array_keys($iter->data)))
			$field = 'content';
		else
			$field = 'content_' . $language;

		$link_to_read = edit_url($_SERVER['REQUEST_URI'], [], ['editable_edit']);

		return $this->twig->render('form.twig', compact('iter', 'language', 'field', 'link_to_read'));
	}
}