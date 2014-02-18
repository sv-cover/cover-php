<?php
require_once 'form.php';
require_once 'markup.php';

class MailinglijstenView extends View
{
	protected $__file = __FILE__;

	public function get_all_commissies()
	{
		$commissie_model = get_model('DataModelCommissie');
		$commissies = $commissie_model->get();
		$values = array();

		foreach ($commissies as $commissie)
			$values[$commissie->get('id')] = $commissie->get('naam');

		return $values;
	}

	public function get_all_toegang_options()
	{
		return array(
			DataModelMailinglijst::TOEGANG_IEDEREEN => __('Iedereen'),
			DataModelMailinglijst::TOEGANG_DEELNEMERS => __('Alleen mensen op de mailinglijst'),
			DataModelMailinglijst::TOEGANG_COVER => __('Alleen *@svcover.nl adressen'),
			DataModelMailinglijst::TOEGANG_EIGENAAR => __('Alleen de commissie van de lijst')
		);
	}
}
