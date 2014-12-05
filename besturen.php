<?php

require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';
require_once 'include/controllers/ControllerEditable.php';

class ControllerBesturen extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelBesturen');
	}

	protected function _get_title($iters = null)
	{
		if ($iters instanceof DataIter)
			return $iters->get('naam');
		else
			return __('Besturen');
	}

	protected function _validate(DataIter $iter, $data, array &$errors)
	{
		if ($iter === null && !isset($data['naam']))
			$errors[] = 'naam';
		elseif (isset($data['naam']) && strlen(trim($data['naam'])) === 0)
			$errors[] = 'naam';

		if ($iter === null && !isset($data['login']))
			$errors[] = 'login';
		elseif (isset($data['login']) && !preg_match('/^[a-z0-9]+$/i', $data['login']))
			$errors[] = 'login';

		return count($errors) === 0;
	}

	protected function _create($data, array &$errors)
	{
		if (!$this->_validate(null, $data, $errors))
			return false;

		$editable_model = get_model('DataModelEditable');

		$page_data = array(
			'owner' => COMMISSIE_BESTUUR,
			'titel' => $data['naam']);

		$iter = new DataIter($editable_model, -1, $page_data);

		$page_id = $editable_model->insert($iter, true);

		$bestuur_data = array(
			'naam' => $data['naam'],
			'login' => $data['login'],
			'nocaps' => strtolower($data['naam']),
			'page' => $page_id);

		return parent::_create($bestuur_data, $errors);
	}

	protected function _update(DataIter $bestuur, $data, array &$errors)
	{
		if (!$this->_validate($bestuur, $data, $errors))
			return false;
		
		$data['nocaps'] = $data['naam'];

		$editable_model = get_model('DataModelEditable');

		$editable = $editable_model->get_iter($bestuur->get('page'));
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
}

$controller = new ControllerBesturen();
$controller->run();
