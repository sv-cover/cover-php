<?php
namespace App\Controller;

use App\Form\BoardType;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

class BoardsController extends \ControllerCRUDForm
{
	protected $view_name = 'boards';
	protected $form_type = BoardType::class;

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelBesturen');

		parent::__construct($request, $router);
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
		{
			$parameters['id'] = $iter->get_id();

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}

		return $this->generate_url('boards', $parameters);
	}

	protected function _get_title($iters = null)
	{
		if ($iters instanceof \DataIter)
			return $iters->get('naam');
		else
			return __('Boards');
	}

	protected function _process_create(\DataIter $iter)
	{
		$editable_model = get_model('DataModelEditable');

		$page_data = array(
			'committee_id' => COMMISSIE_BESTUUR,
			'titel' => $iter['naam']);

		$page = $editable_model->new_iter($page_data);

		$iter['page_id'] = $editable_model->insert($page, true);

		return parent::_process_create($iter);
	}

	protected function _process_update(\DataIter $iter)
	{

		$editable_model = get_model('DataModelEditable');

		$editable = $iter['page'];
		$editable->set('titel', $iter['naam']);
	
		$editable_model->update($editable);

		return parent::_process_update($iter);
	}

	protected function _index()
	{
		// Find all the boards
		$iters = parent::_index();

		// Sort then on their canonical names: $betuur->get('login')
		usort($iters, array($this, '_compare_bestuur'));
		
		return $iters;
	}

	public function _compare_bestuur($left, $right)
	{
		return -1 * strnatcmp($left->get('login'), $right->get('login'));
	}

	public function run_read(\DataIter $iter)
	{
		return $this->view->redirect(sprintf('%s#%s', $this->generate_url('boards'), urlencode($iter['login'])));
	}
}
