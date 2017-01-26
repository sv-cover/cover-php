<?php
require_once 'include/init.php';
require_once 'include/controllers/ControllerCRUD.php';
require_once 'themes/default/views/commissies/commissies.php';

class ControllerCommissies extends ControllerCRUD
{	
	protected $_var_id = 'commissie';

	public $mode;

	public function __construct()
	{
		$this->model = get_model('DataModelCommissie');
		
		$this->view = View::byName('commissies', $this);
	}

	protected function _index()
	{
		return $this->model->get(false);
	}

	protected function _create(DataIter $iter, array $data, array &$errors)
	{
		if (!parent::_create($iter, $data, $errors))
			return false;

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

	/**
	 * Override ControllerCRUD::run_read to also restrict the model to the same type as the iter.
	 */ 
	public function run_read(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter))
			throw new Exception('You are not allowed to read this ' . get_class($iter) . '.');

		// restrict the model to only this type (either committees or working groups)
		// to allow the navigation around the page to match
		$this->model->type = $iter['type'];
		
		return $this->view()->render_read($iter);
	}

	/**
	 * Override ControllerCRUD::link_to to use the login name instead of the id for better links.
	 */
	public function link_to($view, DataIter $iter = null, array $arguments = [])
	{
		$arguments[$this->_var_view] = $view;

		if ($iter !== null)
			$arguments[$this->_var_id] = $iter['login'];

		return $this->link($arguments);
	}

	/**
	 * Index page that shows only working groups.
	 */
	public function run_working_groups()
	{
		$this->model->type = DataModelCommissie::TYPE_WORKING_GROUP;

		$iters = $this->model->get(false);

		return $this->view->render_working_groups($iters); 
	}

	/**
	 * Override the default ControllerCRUD::run_impl to allow either ?commissie= and ?id=.
	 */
	protected function run_impl()
	{
		// Support for old urls
		if (isset($_GET['id']) && !isset($_GET['commissie']))
			$_GET['commissie'] = $_GET['id'];

		return parent::run_impl();
	}
}

$controller = new ControllerCommissies();
$controller->run();

