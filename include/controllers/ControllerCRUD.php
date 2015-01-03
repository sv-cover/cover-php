<?php

require_once 'include/init.php';
require_once 'include/policies/policy.php';
require_once 'include/controllers/Controller.php';

class ControllerCRUD extends Controller
{
	protected $model;

	protected $_var_view = 'view';

	protected $_var_id = 'id';

	protected $_default_view = 'index';

	protected function _create($data, array &$errors)
	{
		$iter_class = $this->model->dataiter;
		
		$iter = new $iter_class($this->model, -1, $data);

		$id = $this->model->insert($iter);

		return new $iter_class($this->model, $id, $iter->data);
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

		$result = $this->model->update($iter);

		return $result === array() || $result === true;
	}

	protected function _delete(DataIter $iter, array &$errors)
	{
		return $this->model->delete($iter) > 0;
	}

	protected function _index()
	{
		return $this->model->get();
	}

	protected function _send_json($data)
	{
		header('Content-Type: application/json');
		echo json_encode($data, JSON_PRETTY_PRINT);
	}

	protected function _send_json_single(DataIter $iter)
	{
		$this->_send_json(array(
			'iter' => $this->_json_augment_iter($iter)
		));
	}

	protected function _send_json_index(array $iters)
	{
		$links = array();

		if (get_policy($this->model)->user_can_create())
			$links['create'] = $this->link_to_create();

		$this->_send_json(array(
			'iters' => array_map(array($this, '_json_augment_iter'), $iters),
			'_links' => $links
		));
	}

	protected function _json_augment_iter(DataIter $iter)
	{
		$links = array();

		if (get_policy($this->model)->user_can_read($iter))
			$links['read'] = $this->link_to_read($iter);

		if (get_policy($this->model)->user_can_update($iter))
			$links['update'] = $this->link_to_update($iter);

		if (get_policy($this->model)->user_can_delete($iter))
			$links['delete'] = $this->link_to_delete($iter);

		return array_merge($iter->data, array('__id' => $iter->get_id(), '__links' => $links));
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

		if ($this->_form_is_submitted('create'))
			if ($iter = $this->_create($_POST, $errors))
				$success = true;

		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				if ($success)
					$this->_send_json_single($iter);
				else
					$this->_send_json(compact('errors'));
				break;

			default:
				if ($success)
					$this->redirect($this->link_to_read($iter));
				else
					$this->get_content('form', new DataIter($this->model, null, array()), compact('errors'));
				break;	
		}
	}

	public function run_read(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter))
			throw new Exception('You are not allowed to read this ' . get_class($iter) . '.');

		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				$this->_send_json_single($iter);
				break;

			default:
				return $this->get_content('single', $iter);
				break;
		}
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

		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				if ($success)
					$this->_send_json_single($iter);
				else
					$this->_send_json(compact('errors'));
				break;

			default:
				if ($success)
					$this->redirect($this->link_to_read($iter));
				else
					$this->get_content('form', $iter, compact('errors'));
				break;	
		}
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

		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				$this->_send_json(compact('errors'));
				break;

			default:
				if ($success)
					$this->redirect($this->link_to_index());
				else
					$this->get_content('confirm_delete', $iter, compact('errors'));
				break;	
		}
	}

	public function run_index()
	{
		$iters = array_filter($this->_index(), array(get_policy($this->model), 'user_can_read'));

		switch ($this->_get_preferred_response())
		{
			case 'application/json':
				$this->_send_json_index($iters);
				break;

			default:
				$this->get_content('index', $iters);
				break;
		}
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
		}

		if (!$view)
			$view = $this->_default_view;

		$method = sprintf('run_%s', $view);

		$self = new ReflectionClass($this);

		if ($self->hasMethod($method) && $self->getMethod($method)->isPublic())
			return call_user_func_array([$this, $method], $iter ? [$iter] : []);
		else
			throw new NotFoundException('view not found');
	}
}
