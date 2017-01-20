<?php

require_once 'include/init.php';
require_once 'include/policies/policy.php';
require_once 'include/controllers/Controller.php';

class ControllerCRUD extends Controller
{
	protected $_var_view = 'view';

	protected $_var_id = 'id';

	protected function _validate(DataIter $iter, array &$data, array &$errors)
	{
		return true;
	}

	protected function _create(DataIter $iter, array $data, array &$errors)
	{
		if (!$this->_validate($iter, $data, $errors))
			return false;

		// TODO: Oh this is soo unsafe! We just set all the data, then insert all the data using the model :(
		$iter->set_all($data);

		// Huh, why are we checking again? Didn't we already check in the run_create() method?
		// Well, yes, but sometimes a policy is picky about how you fill in the data!
		if (!get_policy($iter)->user_can_create($iter))
			throw new UnauthorizedException('You are not allowed to create this DataIter according to the policy.');

		$id = $this->model->insert($iter);

		$iter->set_id($id);

		return true;
	}

	protected function _read($id)
	{
		return $this->model->get_iter($id);
	}

	protected function _update(DataIter $iter, array $data, array &$errors)
	{
		if (!$this->_validate($iter, $data, $errors))
			return false;
		
		foreach ($data as $key => $value)
			$iter->set($key, $value);

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

	protected function _create_view($view)
	{
		return View::byName($view, $this);
	}

	/**
	 * The view needs an empty iter to check the user_can_create policy against.
	 */
	public function new_iter()
	{
		return $this->model->new_iter();
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
		$iter = $this->new_iter();

		if (!get_policy($this->model)->user_can_create($iter))
			throw new Exception('You are not allowed to add new items.');

		$success = false;

		$errors = array();

		if ($this->_form_is_submitted('create'))
			if ($this->_create($iter, $_POST, $errors))
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

		if ($this->_form_is_submitted('update', $iter))
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

		if ($this->_form_is_submitted('delete', $iter))
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

		$view = str_replace('-', '_', $view);

		if (!method_exists($this, 'run_' . $view))
			throw new NotFoundException("View '$view' not implemented by " . get_class($this));

		return call_user_func_array([$this, 'run_' . $view], [$iter]);
	}
}
