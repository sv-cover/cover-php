<?php

class BoardsView extends CRUDView
{
	public function get_summary($page_id)
	{
		$editable_model = get_model('DataModelEditable');
		
		return $editable_model->get_summary($page_id);
	}

	public function has_bestuursfoto($bestuur)
	{
		return file_exists('public/images/boards/' . $bestuur->get('login') . '.jpg');
	}

	public function get_bestuursfoto($bestuur)
	{
		return 'images/boards/' . $bestuur->get('login') . '.jpg';
	}

	public function parse_bestuursfoto($bestuur, $html)
	{
		return preg_replace('/____BESTUURSFOTO____(\s+|\<br\/?\>)*/i', '', $html);
	}
}