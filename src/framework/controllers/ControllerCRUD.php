<?php

require_once 'src/init.php';
require_once 'src/framework/controllers/Controller.php';
require_once 'src/framework/validate.php';
require_once 'src/framework/policy.php';

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

public function path(string $view, DataIter $iter = null, bool $json = false)
{
	throw new LogicException('ContollerCrud::path not implemented');
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

	$view = $this->get_parameter($this->_var_view);

	$id = $this->get_parameter($this->_var_id);

	if (isset($id) && $id != '')
	{
		$iter = $this->_read($id);

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
