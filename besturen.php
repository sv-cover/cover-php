<?php

require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';

class ControllerBesturen extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelBesturen');

		$this->view = View::byName('besturen', $this);
	}

	protected function _get_title($iters = null)
	{
		if ($iters instanceof DataIter)
			return $iters->get('naam');
		else
			return __('Besturen');
	}

	protected function _validate(DataIter $iter, array &$data, array &$errors)
	{
		if (!$iter->has_id() && !isset($data['naam']))
			$errors[] = 'naam';
		elseif (isset($data['naam']) && strlen(trim($data['naam'])) === 0)
			$errors[] = 'naam';

		if (!$iter->has_id() && !isset($data['login']))
			$errors[] = 'login';
		elseif (isset($data['login']) && !preg_match('/^[a-z0-9]+$/i', $data['login']))
			$errors[] = 'login';

		return count($errors) === 0;
	}

	protected function _create(DataIter $iter, array $data, array &$errors)
	{
		if (!$this->_validate($iter, $data, $errors))
			return false;

		$editable_model = get_model('DataModelEditable');

		$page_data = array(
			'owner' => COMMISSIE_BESTUUR,
			'titel' => $data['naam']);

		$page = $editable_model->new_iter($page_data);

		$page_id = $editable_model->insert($page, true);

		$bestuur_data = array(
			'naam' => $data['naam'],
			'login' => $data['login'],
			'nocaps' => strtolower($data['naam']),
			'page_id' => $page_id);

		return parent::_create($iter, $bestuur_data, $errors);
	}

	protected function _update(DataIter $bestuur, array $data, array &$errors)
	{
		if (!$this->_validate($bestuur, $data, $errors))
			return false;
		
		$data['nocaps'] = $data['naam'];

		$editable_model = get_model('DataModelEditable');

		$editable = $bestuur['page'];
		$editable->set('titel', $data['naam']);
	
		$editable_model->update($editable);

		return parent::_update($bestuur, $data, $errors);
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

	public function run_read(DataIter $iter)
	{
		return $this->view->redirect($this->link_to_read($iter));
	}

	public function link_to_read(DataIter $iter)
	{
		return sprintf('besturen.php#%s', urlencode($iter['login']));
	}
}

$controller = new ControllerBesturen();
$controller->run();
