<?php

require_once 'include/init.php';
require_once 'include/policies/policy.php';
require_once 'include/controllers/Controller.php';

class ControllerCRUD extends Controller
{
	protected $_var_view = 'view';

	protected $_var_id = 'id';

	protected function _create(DataIter $iter, $data, array &$errors)
	{
		$iter->set_all($data);

		$id = $this->model->insert($iter);

		$dataiter_class = new ReflectionClass($iter);
		return $dataiter_class->newInstance($this->model, $id, $iter->data);
	}

	protected function _read($id)
	{
		return $this->model->get_iter($id);
	}

	protected function _update(DataIter $iter, $data, array &$errors)
	{
		foreach ($data as $key => $value)
			if (is_scalar($value))
				$iter->set($key, trim($value));
			elseif (is_null($value))
				$iter->set($key, null);

		return $this->model->update($iter) > 0;
	}

	protected function _delete(DataIter $iter, array &$errors)
	{
		return $this->model->delete($iter) > 0;
	}

	protected function _index()
	{
		return $this->model->get();
	}

	protected function _form_is_submitted($form)
	{
		return $_SERVER['REQUEST_METHOD'] == 'POST';
			// && !empty($_POST['_' . $form . '_nonce'])
			// && in_array($_POST['_' . $form . '_nonce'], $_SESSION[$form . '_nonce']);
	}

	protected function _create_view($view)
	{
		return View::byName($view, $this);
	}

	protected function _create_iter()
	{
		$dataiter_class = new ReflectionClass($this->model->dataiter);
		return $dataiter_class->newInstance($this->model, null, array());
	}

	public function link(array $arguments)
	{
		return sprintf('%s?%s', $_SERVER['SCRIPT_NAME'], http_build_query($arguments));
	}

	protected function link_to_iter(DataIter $iter, array $arguments = array())
	{
		return $this->link(array_merge(array($this->_var_id => $iter->get_id()), $arguments));
	}

	public function link_to_create()
	{
		return $this->link([$this->_var_view => 'create']);
	}

	public function link_to_read(DataIter $iter)
	{
		return $this->link_to_iter($iter, [$this->_var_view => 'read']);
	}

	public function link_to_update(DataIter $iter)
	{
		return $this->link_to_iter($iter, [$this->_var_view => 'update']);
	}

	public function link_to_delete(DataIter $iter)
	{
		return $this->link_to_iter($iter, [$this->_var_view => 'delete']);
	}

	public function link_to_index()
	{
		return $_SERVER['SCRIPT_NAME'];
	}

	public function run_create()
	{
		if (!get_policy($this->model)->user_can_create())
			throw new Exception('You are not allowed to add new items.');

		$success = false;

		$errors = array();

		$iter = $this->_create_iter();

		if ($this->_form_is_submitted('create'))
			if ($iter = $this->_create($iter, $_POST, $errors))
				$success = true;

		return $this->view()->render_create($iter, $success, $errors);
	}

	public function run_read(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter))
			throw new Exception('You are not allowed to read this ' . get_class($iter) . '.');

		return $this->view()->render_read($iter);
	}

	public function run_update(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_update($iter))
			throw new Exception('You are not allowed to edit this ' . get_class($iter) . '.');

		$success = false;

		$errors = array();

		if ($this->_form_is_submitted('update'))
			if ($this->_update($iter, $_POST, $errors))
				$success = true;

		return $this->view()->render_update($iter, $success, $errors);
	}

	public function run_delete(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_delete($iter))
			throw new Exception('You are not allowed to delete this ' . get_class($iter) . '.');

		$success = false;

		$errors = array();

		if ($this->_form_is_submitted('delete'))
			if ($this->_delete($iter, $errors))
				$success = true;

		return $this->view()->render_delete($iter, $success, $errors);
	}

	public function run_index()
	{
		$iters = array_filter($this->_index(), array(get_policy($this->model), 'user_can_read'));

		return $this->view()->render_index($iters);
	}
	
	protected function run_impl()
	{
		$iter = null;

		$view = isset($_GET[$this->_var_view]) ? $_GET[$this->_var_view] : null;

		if (isset($_GET[$this->_var_id]) && $_GET[$this->_var_id] != '')
		{
			$iter = $this->_read($_GET[$this->_var_id]);

			if (!$view)
				$view = 'read';

			if (!$iter)
				throw new NotFoundException('ControllerCRUD::_read could not find the model instance.');
		}

		if (!$view)
			$view = 'index';

		if (!method_exists($this, 'run_' . $view))
			throw new NotFoundException('View not implemented by this ControllerCRUD');
			
		return call_user_func_array([$this, 'run_' . $view], [$iter]);
	}
}
