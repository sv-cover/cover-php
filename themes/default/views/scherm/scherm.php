<?php
require_once('include/markup.php');

class SchermView extends View {
	protected $__file = __FILE__;

	function format_date($punt)
	{
		$days = get_days();
		$months = get_months();

		return sprintf('%s %d %s, %d:%02d',
			$days[$punt->get('vandagnaam')],
			$punt->get('vandatum'),
			$months[$punt->get('vanmaand')],
			$punt->get('vanuur'), $punt->get('vanminuut'));
	}
}
