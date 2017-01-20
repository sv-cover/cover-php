<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerShow extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelEditable');

		$this->view = View::byName('show', $this);
	}

	protected function _validate(DataIter $iter, array &$data, array &$errors)
	{
		if (!isset($data['owner']))
			$errors[] = 'owner';

		elseif (!get_identity()->member_in_committee($data['owner'])
			&& !get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			&& !get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			$errors[] = 'owner';

		return count($errors) === 0;
	}

	protected function _update(DataIter $iter, array $data, array &$errors)
	{
		$content_fields = ['content', 'content_en'];

		// Store the current values for diffing 
		$old_data = array();
		foreach ($content_fields as $field)
			$old_data[$field] = $iter->data[$field];

		// Update as usual
		$success = parent::_update($iter, $data, $errors);

		// If the update succeeded (i.e. _validate came through positive)
		// send a notification email to those who are interested.
		if ($success)
		{
			foreach ($content_fields as $field)
			{
				// Only notify about changed content, skip equal stuff
				if ($iter->data[$field] == $old_data[$field])
					continue;

				$diff = implode("\n", [
					'New content:',
					$iter->data[$field],
					'', '',
					'Old content:',
					$old_data[$field]
				]);
				
				$mail_data = $this->_prepare_mail($iter, $diff);

				$mail_data['taal'] = $field == 'content' ? 'nl' : 'en';
				
				if (!empty($mail_data['email']))
				{
					$body = parse_email('editable_edit.txt', $mail_data);
					$subject = sprintf('Page %s has been updated on the Cover website', $mail_data['titel']);

					foreach ($mail_data['email'] as $email)
						@mail($email, $subject, $body, "From: acdcee@svcover.nl\r\n");
				}
			}
		}

		return $success;
	}

	private function _prepare_mail(DataIter $iter, $difference)
	{
		$data = $iter->data;
		$data['member_naam'] = member_full_name(get_identity()->member(), IGNORE_PRIVACY);
		$data['page'] = $difference;

		$commissie_model = get_model('DataModelCommissie');

		$in_bestuur = member_in_commissie(COMMISSIE_BESTUUR, false);
		$in_commissie = member_in_commissie($iter['owner'], false);
		
		if (!$in_commissie && $in_bestuur) {
			/* Bestuur changed something, notify commissie */
			$data['commissie_naam'] = $commissie_model->get_naam(COMMISSIE_BESTUUR);
			$data['email'] = array($commissie_model->get_email($iter->get('owner')));
		} elseif (!$in_bestuur && $in_commissie) {
			/* Commissie changed something, notify bestuur */
			$data['commissie_naam'] = $commissie_model->get_naam($iter->get('owner'));
			$data['email'] = array(get_config_value('email_bestuur'));
		} else {
			/* Easy changed something, notify bestuur and commissie */
			$data['commissie_naam'] = $commissie_model->get_naam(COMMISSIE_EASY);
			$data['email'] = array(
				$commissie_model->get_email($iter->get('owner')),
				get_config_value('email_bestuur'));
		}
		
		return $data;		
	}

	public function run_preview(DataIterEditable $iter = null)
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST')
			throw new RuntimeException('This page is only intended to preview submitted content');

		$page = new DataIterEditable($this->model, null, $_POST);

		return editable_parse($page, null);
	}

	public function run_index()
	{
		return $this->view->redirect('index.php'); // we don't have no index/sitemap (yet)
	}

	public function run_read(DataIter $iter)
	{
		if ($committee = $this->_is_embedded_page($iter['id'], 'DataModelCommissie'))
			return $this->view->redirect('commissies.php?id=' . $committee->get('login'), true);
		elseif ($board = $this->_is_embedded_page($iter['id'], 'DataModelBesturen'))
			return $this->view->redirect('besturen.php#' .  rawurlencode($board->get('login')), true);
		elseif ($iter['id'] == 26) // TODO this is a dirty hackish way :(
			return $this->view->redirect('boeken.php');
		elseif ($iter['id'] == 21)
			return $this->view->redirect('index.php');
		else
			return parent::run_read($iter);
	}

	protected function _is_embedded_page($page_id, $model_name)
	{
		$model = get_model($model_name);
		return $model->get_from_page($page_id);
	}
}

$controller = new ControllerShow();
$controller->run();
