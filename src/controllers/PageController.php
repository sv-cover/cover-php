<?php
namespace App\Controller;

require_once 'src/framework/init.php';
require_once 'src/controllers/ControllerCRUD.php';

class PageController extends \ControllerCRUD
{
	protected $view_name = 'page';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelEditable');

		parent::__construct($request, $router);
	}

	/* These two can_set_* checks are used by the view to check whether it needs to display the fields */
	public function can_set_titel(\DataIter $iter)
	{
		return !$iter->has_id() || get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function can_set_committee_id(\DataIter $iter)
	{
		return !$iter->has_id()
			|| get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public function new_iter()
	{
		$iter = parent::new_iter();

		// Default to owner = board
		if ($this->can_set_committee_id($iter))
			$iter['committee_id'] = COMMISSIE_BESTUUR;

		return $iter;
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [];

		if ($json)
			$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));

		if ($view === 'create')
			return $this->generate_url('page.create', $parameters);

		if ($view === 'preview')
			return $this->generate_url('page.preview', $parameters);

		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
			$parameters['id'] = $iter->get_id();

		return $this->generate_url('page', $parameters);
	}

	protected function _update(\DataIter $iter, array $data, array &$errors)
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

	private function _prepare_mail(\DataIter $iter, $difference)
	{
		$data = $iter->data;
		$data['member_naam'] = member_full_name(get_identity()->member(), IGNORE_PRIVACY);
		$data['page'] = $difference;

		$commissie_model = get_model('DataModelCommissie');

		$in_bestuur = get_identity()->member_in_committee(COMMISSIE_BESTUUR);
		$in_commissie = get_identity()->member_in_committee($iter['committee_id']);
		
		if (!$in_commissie && $in_bestuur) {
			/* Bestuur changed something, notify commissie */
			$data['commissie_naam'] = $commissie_model->get_naam(COMMISSIE_BESTUUR);
			$data['email'] = array($commissie_model->get_email($iter['committee_id']));
		} elseif (!$in_bestuur && $in_commissie) {
			/* Commissie changed something, notify bestuur */
			$data['commissie_naam'] = $commissie_model->get_naam($iter['committee_id']);
			$data['email'] = array($commissie_model->get_email(COMMISSIE_BESTUUR));
		} else {
			/* AC/DCee changed something, notify bestuur and commissie */
			$data['commissie_naam'] = $commissie_model->get_naam(COMMISSIE_EASY);
			$data['email'] = array(
				$commissie_model->get_email($iter['committee_id']),
				$commissie_model->get_email(COMMISSIE_BESTUUR));
		}
		
		return $data;
	}

	public function run_preview(\DataIterEditable $iter = null)
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST')
			throw new \RuntimeException('This page is only intended to preview submitted content');

		$page = new \DataIterEditable($this->model, null, $_POST);

		return $this->view->render_preview($page, $this->get_parameter('lang'));
	}

	public function run_index()
	{
		return $this->view->redirect($this->generate_url('homepage')); // we don't have no index/sitemap (yet)
	}

	public function run_read(\DataIter $iter)
	{
		if ($committee = $this->_is_embedded_page($iter['id'], 'DataModelCommissie'))
			return $this->view->redirect($this->generate_url('committees', ['commissie' => $committee->get('login')]), true);
		elseif ($board = $this->_is_embedded_page($iter['id'], 'DataModelBesturen'))
			return $this->view->redirect(sprintf('%s#%s', $this->generate_url('boards'), rawurlencode($board->get('login'))), true);
		elseif ($iter['id'] == 26) // TODO this is a dirty hackish way :(
			return $this->view->redirect($this->generate_url('books'));
		elseif ($iter['id'] == 21)
			return $this->view->redirect($this->generate_url('homepage'));
		else
			return parent::run_read($iter);
	}

	protected function _is_embedded_page($page_id, $model_name)
	{
		$model = get_model($model_name);
		return $model->get_from_page($page_id);
	}
}
