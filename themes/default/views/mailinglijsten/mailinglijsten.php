<?php
require_once 'include/form.php';
require_once 'include/markup.php';
require_once 'include/svg.php';

class MailinglijstenView extends CRUDView
{
	public function committee_options()
	{
		$commissie_model = get_model('DataModelCommissie');
		$commissies = $commissie_model->get(null, true);
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
			DataModelMailinglist::TOEGANG_DEELNEMERS => __('Alleen mensen op de mailinglijst'),
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

	public function render_subscribe_member_form(DataIterMailinglist $list, array $errors)
	{
		return $this->render('subscribe_member_form.twig', compact('list', 'errors'));
	}

	public function render_subscribe_guest_form(DataIterMailinglist $list, array $errors)
	{
		return $this->render('subscribe_guest_form.twig', compact('list', 'errors'));
	}

	public function render_archive_index(DataIterMailinglist $list, $messages)
	{
		return $this->render('archive_index.twig', compact('list', 'messages'));
	}

	public function render_archive_read(DataIterMailinglist $list, DataIterMailinglistArchive $message)
	{
		$html_body = null;
		$text_body = null;
		$subject = null;
		$error = null;

		try {
			$parsed = Cover\email\MessagePart::parse_text($message['bericht']);

			$subject = $parsed->header('Subject');

			foreach ($parsed->textParts() as $part) {
				if (!preg_match('/^text\/(html|plain)\b/i', $part->header('Content-Type'), $match))
					continue;

				switch ($match[1]) {
					case 'html':
						$html_body = $part->body();
						break;

					case 'plain':
						$text_body = $part->body();
						break;
				}
			}
		} catch (Exception $e) {
			$error = $e;
		}
		
		return $this->render('archive_single.twig', compact('list', 'message', 'subject', 'html_body', 'text_body', 'error'));
	}

	public function render_embedded(DataIterMailinglist $list, DataModelMailinglist $model)
	{
		return $this->render('embedded.twig', compact('list', 'embedded'));
	}

	public function barchart($data)
	{
		return \cover\svg\barchart($data);
	}
}
