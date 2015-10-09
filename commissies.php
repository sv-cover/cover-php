<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';
require_once 'include/controllers/ControllerEditable.php';

class ControllerCommissies extends ControllerCRUD
{	
	protected $_var_id = 'commissie';

	public function __construct()
	{
		$this->model = get_model('DataModelCommissie');
		$this->model->type = DataModelCommissie::TYPE_COMMITTEE;
	}

	protected function _index()
	{
		return $this->model->get(false);
	}

	protected function _create(array $data, array &$errors)
	{
		$iter = parent::_create($data, $errors);

		if (!empty($data['members']))
			$this->model->set_members($iter, $data['members']);

		return $iter;
	} 

	protected function _update(DataIter $iter, array $data, array &$errors)
	{
		if (!parent::_update($iter, $data, $errors))
			return false;

		$this->model->set_members($iter, $data['members'] ? $data['members'] : array());

		return true;
	}

	protected function _read($id)
	{
		if (!ctype_digit($id))
			return $this->model->get_from_name($id);
		else
			return parent::_read($id);
	}

	public function link_to_iter(DataIter $iter, array $arguments)
	{
		return $this->link(array_merge(array($this->_var_id => $iter->get('login')), $arguments));
	}

	public function link_to_read(DataIter $iter)
	{
		return $this->link_to_iter($iter, array());
	}

	/*
	public function link(array $arguments)
	{
		$link = '/committees';

		if (isset($arguments['view'])) {
			$link .= '/' . $arguments['view'];
			unset($arguments['view']);
		}

		if (isset($arguments['id'])) {
			$link .= '/' . $arguments['id'];
			unset($arguments['id']);
		}

		if (!empty($arguments))
			$link .= '?' . http_build_query($arguments);

		return $link;
	}
	*/

	protected function _get_title($iter)
	{
		return $iter instanceof DataIter ? $iter->get('naam') : __('Commissies');
	}

	/* protected */ function run_impl()
	{
		// Support for old urls
		if (isset($_GET['id']) && !isset($_GET['commissie']))
			$_GET['commissie'] = $_GET['id'];

		return parent::run_impl();
	}
}

$controller = new ControllerCommissies();
$controller->run();

