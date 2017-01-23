<?php
require_once 'include/form.php';
require_once 'include/markup.php';

class MailinglijstenView extends CRUDView
{
	public function committee_options()
	{
		$commissie_model = get_model('DataModelCommissie');
		$commissies = $commissie_model->get();
		$values = array();

		foreach ($commissies as $commissie)
			$values[$commissie->get('id')] = $commissie->get('naam');

		return $values;
	}

	public function type_options()
	{
		return array(
			DataModelMailinglist::TYPE_OPT_IN => __('Opt-in'),
			DataModelMailinglist::TYPE_OPT_OUT => __('Opt-out')
		);
	}

	public function toegang_options()
	{
		return array(
			DataModelMailinglist::TOEGANG_IEDEREEN => __('Iedereen'),
			DataModelMailinglist::TOEGANG_DEELNEMERS => __('Alleen mensen op de mailinglist'),
			DataModelMailinglist::TOEGANG_COVER => __('Alleen *@svcover.nl adressen'),
			DataModelMailinglist::TOEGANG_EIGENAAR => __('Alleen de commissie van de lijst')
		);
	}

	public function uid(DataIterMailinglistSubscription $abonnement)
	{
		return sprintf('aanmelding%s', $abonnement['abonnement_id'] ? $abonnement['abonnement_id'] : $abonnement['lid_id']);
	}

	public function render_unsubscribe_form(DataIterMailinglist $list, DataIterMailinglistSubscription $subscription)
	{
		return $this->render('unsubscribe_form.twig', compact('list', 'subscription'));
	}

	public function render_autoresponder_form(DataIterMailinglist $iter, $autoresponder, $success, $errors)
	{
		return $success
			? $this->redirect($this->controller->link_to_update($iter))
			: $this->render('autoresponder_form.twig', compact('iter', 'autoresponder', 'success', 'errors'));
	}
}
