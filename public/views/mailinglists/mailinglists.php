<?php
require_once 'src/framework/form.php';
require_once 'src/framework/markup.php';
require_once 'src/framework/svg.php';
require_once 'src/framework/send-mailinglist-mail.php';

class MailinglistsView extends CRUDView
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
			DataModelMailinglist::TOEGANG_IEDEREEN => __('Everyone'),
			DataModelMailinglist::TOEGANG_DEELNEMERS => __('Only people subscribed to this list (and the list owner)'),
			DataModelMailinglist::TOEGANG_COVER => __('Only *@svcover.nl addresses'),
			DataModelMailinglist::TOEGANG_EIGENAAR => __('Only the committee that owns this list')
			DataModelMailinglist::TOEGANG_COVER_DEELNEMERS => __('People subscribed to this list and *@svcover.nl addresses'),
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
			? $this->redirect($this->controller->generate_url('mailing_lists', ['view' => 'update', 'id' => $iter->get_id()]))
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
				if (preg_match('/^text\/html\b/i', $part->header('Content-Type')))
					$html_body = $part->body();
				else
					$text_body = $part->body();
			}
		} catch (Exception $e) {
			$error = $e;
		}
		
		return $this->render('archive_single.twig', compact('list', 'message', 'subject', 'html_body', 'text_body', 'error'));
	}

	public function render_embedded(DataIterMailinglist $list, DataModelMailinglist $model)
	{
		return $this->render('embedded.twig', compact('list'));
	}

	public function barchart($data)
	{
		return \Cover\svg\barchart($data);
	}

	public function readable_status($code)
	{
		if ($code == 0)
			return 'Success';
		return \Cover\email\mailinglist\get_error_message($code);
	}
}
