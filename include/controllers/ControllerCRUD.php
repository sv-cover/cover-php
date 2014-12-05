<?php

require_once 'include/init.php';
require_once 'include/policies/policy.php';
require_once 'include/controllers/Controller.php';

class ControllerCRUD extends Controller
{
	protected $_var_view = 'view';

	protected $_var_id = 'id';

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

	protected function _form_is_submitted($form)
	{
		return $_SERVER['REQUEST_METHOD'] == 'POST';
			// && !empty($_POST['_' . $form . '_nonce'])
			// && in_array($_POST['_' . $form . '_nonce'], $_SESSION[$form . '_nonce']);
	}

	protected function run_view($view, DataModel $model, $iter, array $params)
	{
		list($view, $method) = explode('::', $view, 2);

		$view_class = sprintf('%sView', $view);

		$search_paths = array(
			'themes/' . get_theme() . '/views/' . $view . '/' . $view . '.php',
			'themes/default/views/' . $view . '/' . $view . '.php');

		$path = find_file($search_paths);

		if ($path === null)
			throw new RuntimeException("Could not find view class '$view_class' while trying to run view $view::$method.");

		include_once $path;

		$instance = new $view_class($this);

		call_user_func([$instance, $method], array_merge($params, compact('model', 'iter')));
	}

	protected function _get_title($iters = null)
	{
		return '';
	}

	protected function _get_view_name()
	{
		return strtolower(substr(get_class($this), strlen('Controller')));
	}

	protected function _get_default_view_params()
	{
		return array_merge(
			get_object_vars($this), // stuff like 'model' and other user defined stuff
			array('controller' => $this));
	}

	protected function _get_preferred_response()
	{
		return parse_http_accept($_SERVER['HTTP_ACCEPT'],
			array('application/json', 'text/html', '*/*'));
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

	protected function get_content($view, $iters = null, array $params = array())
	{
		if (!$this->embedded)
			$this->run_header(array('title' => $this->_get_title($iters)));

		if (strpos($view, '::') === false)
			$view = $this->_get_view_name() . '::' . $view;

		$this->run_view($view, $this->model, $iters, array_merge($this->_get_default_view_params(), $params));

		if (!$this->embedded)
			$this->run_footer();
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
	
	/* protected */ function run_impl()
	{
		$iter = null;

		$view = isset($_GET[$this->_var_view]) ? $_GET[$this->_var_view] : null;

		if (isset($_GET[$this->_var_id]) && $_GET[$this->_var_id] != '')
		{
			$iter = $this->_read($_GET[$this->_var_id]);

			if (!$view)
				$view = 'read';

			if (!$iter)
				return run_view('common::not_found');
		}

		switch ($view ?: 'index')
		{
			case 'create':
				return $this->run_create();

			case 'read':
				return $this->run_read($iter);
			
			case 'update':
				return $this->run_update($iter);

			case 'delete':
				return $this->run_delete($iter);

			case 'index':
				return $this->run_index();
			
			default:
				return run_view('common::not_found');
		}
	}
}
