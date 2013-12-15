<?php

require_once 'editable.php';

class BesturenView extends View
{
	protected $__file = __FILE__;

	public function get_summary($page_id)
	{
		$editable_model = get_model('DataModelEditable');
		
		$page = $editable_model->get_iter($page_id);
		
		$content = $page->get('content');
	
		return editable_get_summary($content, $page->get('owner'));
	}
}
