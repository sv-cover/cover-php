<?php

require_once 'include/init.php';
require_once 'include/policies/policy.php';
require_once 'controllers/Controller.php';

class ControllerCRUD extends Controller
{
	protected function _create($data, array &$errors)
	{
		$iter = new DataIter($this->model, -1, $data);

		$id = $this->model->insert($iter, true);

		return new DataIter($this->model, $id, $iter->data);
	}

	protected function _read($id)
	{
		return $this->model->get_iter($id);
	}

	protected function _update(DataIter $iter, $data, array &$erros)
	{
		foreach ($data as $key => $value)
			$iter->set($key, trim($value));

		$result = $this->model->update($iter);

		return $result === array() || $result === true;
	}

	protected function _delete(DataIter $iter, array &$errors)
	{
		$result = $this->model->delete($iter);

		return $result === array() || $result === true;
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

	protected function _redirect($link)
	{
		header('Location: ' . $link);
		echo '<a href="' . htmlentities($link, ENT_QUOTES) . '">' . __('Je wordt doorgestuurd. Klik hier om verder te gaan.') . '</a>';
	}

	public function link(array $arguments)
	{
		return sprintf('%s?%s', $_SERVER['SCRIPT_NAME'], http_build_query($arguments));
	}

	protected function link_to_iter(DataIter $iter, array $arguments = array())
	{
		return $this->link(array_merge(array('id' => $iter->get_id()), $arguments));
	}

	public function link_to_create()
	{
		return $this->link(['view' => 'create']);
	}

	public function link_to_read(DataIter $iter)
	{
		return $this->link_to_iter($iter, ['view' => 'read']);
	}

	public function link_to_update(DataIter $iter)
	{
		return $this->link_to_iter($iter, ['view' => 'update']);
	}

	public function link_to_delete(DataIter $iter)
	{
		return $this->link_to_iter($iter, ['view' => 'delete']);
	}

	public function link_to_index()
	{
		return $_SERVER['SCRIPT_NAME'];
	}

	public function run_create()
	{
		if (!get_policy($this->model)->user_can_create())
			throw new Exception('You are not allowed to add new items.');

		$errors = array();

		if ($this->_form_is_submitted('create'))
			if ($iter = $this->_create($_POST, $errors))
				$this->_redirect($this->link_to_read($iter));

		return $this->get_content('form', new DataIter($this->model, null, array()), compact('errors'));
	}

	public function run_read(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter))
			throw new Exception('You are not allowed to read this ' . get_class($iter) . '.');

		return $this->get_content('single', $iter);
	}

	public function run_update(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_update($iter))
			throw new Exception('You are not allowed to edit this ' . get_class($iter) . '.');

		$errors = array();

		if ($this->_form_is_submitted('update'))
			if ($this->_update($iter, $_POST, $errors))
				$this->_redirect($this->link_to_read($iter));

		return $this->get_content('form', $iter, compact('errors'));
	}

	public function run_delete(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_delete($iter))
			throw new Exception('You are not allowed to delete this ' . get_class($iter) . '.');

		$errors = array();

		if ($this->_form_is_submitted('delete'))
			if ($iter = $this->_delete($iter, $errors))
				$this->_redirect($this->link_to_index());

		return $this->get_content('confirm_delete', $iter, compact('errors'));
	}

	public function run_index()
	{
		$iters = array_filter($this->_index(), array(get_policy($this->model), 'user_can_read'));

		return $this->get_content('index', $iters);
	}
	
	/* protected */ function run_impl()
	{
		$iter = null;

		if (isset($_GET['id']) && $_GET['id'] != '')
		{
			$iter = $this->_read($_GET['id']);

			if (!$iter)
				return run_view('common::not_found');
		}

		switch (isset($_GET['view']) ? $_GET['view'] : 'index')
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
