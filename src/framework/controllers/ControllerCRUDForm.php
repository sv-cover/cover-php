<?php

require_once 'src/init.php';
require_once 'src/framework/controllers/Controller.php';
require_once 'src/framework/policy.php';

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ControllerCRUDForm extends Controller
{
	protected $_var_view = 'view';

	protected $_var_id = 'id';

	protected $form_type;

	// Equivalent for _create, but prevent issues with incompatible signature…
	protected function _create(\DataIter $iter, FormInterface $form)
	{
		// Huh, why are we checking again? Didn't we already check in the run_create() method?
		// Well, yes, but sometimes a policy is picky about how you fill in the data!
		if (!\get_policy($iter)->user_can_create($iter))
			throw new \UnauthorizedException('You are not allowed to create this DataIter according to the policy.');

		$id = $this->model->insert($iter);

		$iter->set_id($id);

		return true;
	}

	protected function _read($id)
	{
		return $this->model->get_iter($id);
	}

	// Equivalent for _update, but prevent issues with incompatible signature…
	protected function _update(\DataIter $iter, FormInterface $form)
	{
		return $this->model->update($iter) > 0;
	}

	// Equivalent for _delete, but prevent issues with incompatible signature…
	protected function _delete(\DataIter $iter)
	{
		return $this->model->delete($iter) > 0;
	}

	protected function _index()
	{
		return $this->model->get();
	}

	/**
	 * The view needs an empty iter to check the user_can_create policy against.
	 */
	public function new_iter()
	{
		return $this->model->new_iter();
	}

	public function path(string $view, DataIter $iter = null)
	{
		throw new LogicException('ContollerCrud::path not implemented');
	}

	public function get_form(\DataIter $iter = null)
	{
		if (!isset($this->form_type))
			throw new \LogicException('FormType not set on controller');
		$form = $this->createForm($this->form_type, $iter, ['mapped' => false]);
		$form->handleRequest($this->get_request());
		return $form;
	}

	public function get_delete_form(\DataIter $iter = null)
	{
		$form = $this->createFormBuilder($iter)
			->add('submit', SubmitType::class, ['label' => __('Delete'), 'color' => 'danger'])
			->getForm();
		$form->handleRequest($this->get_request());
		return $form;
	}

	public function run_create()
	{
		$iter = $this->new_iter();

		if (!\get_policy($this->model)->user_can_create($iter))
			throw new \UnauthorizedException('You are not allowed to add new items.');

		$success = false;

		$form = $this->get_form($iter);

		if ($form->isSubmitted() && $form->isValid()) {
			if ($this->_create($iter, $form))
				$success = true;
			else
				$form->addError(new FormError(__('Something went wrong while processing the form.')));
		}

		return $this->view()->render_create($iter, $form, $success);
	}

	public function run_read(DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter))
			throw new UnauthorizedException('You are not allowed to read this ' . get_class($iter) . '.');

		return $this->view()->render_read($iter);
	}

	public function run_update(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_update($iter))
			throw new \UnauthorizedException('You are not allowed to edit this ' . get_class($iter) . '.');

		$success = false;

		$form = $this->get_form($iter);

		if ($form->isSubmitted() && $form->isValid()) {
			if ($this->_update($iter, $form))
				$success = true;
			else
				$form->addError(new FormError(__('Something went wrong while processing the form.')));
		}

		return $this->view()->render_update($iter, $form, $success);
	}

	public function run_delete(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_delete($iter))
			throw new \UnauthorizedException('You are not allowed to delete this ' . get_class($iter) . '.');

		$success = false;

		$form = $this->get_delete_form($iter);

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_delete($iter))
				$success = true;

		return $this->view()->render_delete($iter, $form, $success);
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
