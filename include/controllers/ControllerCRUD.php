<?php

require_once 'include/init.php';
require_once 'include/policies/policy.php';
require_once 'include/controllers/Controller.php';
require_once 'include/validate.php';

class ControllerCRUD extends Controller
{
	protected $_var_view = 'view';

	protected $_var_id = 'id';

	protected function _validate(DataIter $iter, array $data, array &$errors)
	{
		return validate_dataiter($iter, $data, $errors);
	}

	protected function _create(DataIter $iter, array $input, array &$errors)
	{
		$data = $this->_validate($iter, $input, $errors);

		if ($data === false)
			return false;

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

	protected function _update(DataIter $iter, array $input, array &$errors)
	{
		$data = $this->_validate($iter, $input, $errors);

		if ($data === false)
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

	public function link_to($view, DataIter $iter = null, array $arguments = [])
	{
		$arguments[$this->_var_view] = $view;

		if ($iter !== null)
			$arguments[$this->_var_id] = $iter->get_id();

		return $this->link($arguments);
	}

	public function link_to_create()
	{
		return $this->link_to('create');
	}

	public function link_to_read(DataIter $iter)
	{
		return $this->link_to('read', $iter);
	}

	public function link_to_update(DataIter $iter)
	{
		return $this->link_to('update', $iter);
	}

	public function link_to_delete(DataIter $iter)
	{
		return $this->link_to('delete', $iter);
	}

	public function json_link_to_create()
	{
		$new_iter = $this->model()->new_iter();
		$nonce = nonce_generate(nonce_action_name('create', [$new_iter]));
		return $this->link([$this->_var_view => 'create', '_nonce' => $nonce]);
	}

	public function json_link_to_read(DataIter $iter)
	{
		return $this->link_to('read', $iter, []);
	}

	public function json_link_to_update(DataIter $iter)
	{
		$nonce = nonce_generate(nonce_action_name('update', [$iter]));
		return $this->link_to('update', $iter, ['_nonce' => $nonce]);
	}

	public function json_link_to_delete(DataIter $iter)
	{
		$nonce = nonce_generate(nonce_action_name('delete', [$iter]));
		return $this->link_to('delete', $iter, ['_nonce' => $nonce]);
	}

	public function link_to_index()
	{
		if (isset($this->router) && isset($this->parameters) && isset($this->parameters['_route']))
			return $this->router->generate($this->parameters['_route']);
		else
			// TODO: Should we even be allowed to be in this situation?
			return '?';
	}

	public function run_create()
	{
		$iter = $this->new_iter();

		if (!get_policy($this->model)->user_can_create($iter))
			throw new UnauthorizedException('You are not allowed to add new items.');

		$success = false;

		$errors = array();

		if ($this->_form_is_submitted('create', $iter))
			if ($this->_create($iter, $_POST, $errors))
				$success = true;

		return $this->view()->render_create($iter, $success, $errors);
	}

	public function run_read(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter))
			throw new UnauthorizedException('You are not allowed to read this ' . get_class($iter) . '.');

		return $this->view()->render_read($iter);
	}

	public function run_update(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_update($iter))
			throw new UnauthorizedException('You are not allowed to edit this ' . get_class($iter) . '.');

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
			throw new UnauthorizedException('You are not allowed to delete this ' . get_class($iter) . '.');

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

		try {
			$method = new ReflectionMethod($this, 'run_' . $view);

			if ($method->getNumberOfRequiredParameters() > 1)
				throw new LogicException('trying to call run_' . $view . ' which requires more than one argument');

			if ($method->getNumberOfRequiredParameters() === 1 && $iter === null)
				throw new NotFoundException($view . ' requires an iterator, but none was specified');

			return call_user_func([$this, 'run_' . $view], $iter);
		} catch (ReflectionException $e) {
			throw new NotFoundException("View '$view' not implemented by " . get_class($this));
		}
	}
}
