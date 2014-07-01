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

	public function get_all_type_options()
	{
		return array(
			DataModelMailinglijst::TYPE_OPT_IN => __('Opt-in'),
			DataModelMailinglijst::TYPE_OPT_OUT => __('Opt-out')
		);
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

	public function uid($abonnement)
	{
		return sprintf('aanmelding%s',
			$abonnement->has('abonnement_id')
				? $abonnement->get('abonnement_id')
				: $abonnement->get('lid_id'));
	}
}
