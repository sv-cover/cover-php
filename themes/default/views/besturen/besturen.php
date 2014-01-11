<?php

require_once 'editable.php';

class BesturenView extends View
{
	protected $__file = __FILE__;

	public function get_summary($page_id)
	{
		$editable_model = get_model('DataModelEditable');
		
		return $editable_model->get_summary($page_id);
	}

	public function has_bestuursfoto($bestuur)
	{
		return file_exists('images/besturen/' . $bestuur->get('login') . '.jpg');
	}

	public function get_bestuursfoto($bestuur)
	{
		return 'images/besturen/' . $bestuur->get('login') . '.jpg';
	}

	public function parse_bestuursfoto($bestuur, $html)
	{
		// Hack: don't apply this filter when we are editing the page
		if (isset($_GET['editable_edit']))
			return $html;

		if ($this->has_bestuursfoto($bestuur))
			$img_html = sprintf('<img src="%s" width="100%%">',
				htmlspecialchars($this->get_bestuursfoto($bestuur), ENT_QUOTES));
		else
			$img_html = '';

		return str_replace('____BESTUURSFOTO____', $img_html, $html);
	}
}
