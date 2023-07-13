<?php

require_once 'src/init.php';
require_once 'src/framework/controllers/ControllerCRUD.php';
require_once 'src/framework/policy.php';

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ControllerCRUDForm extends ControllerCRUD
{
	protected $form_type;

	// Equivalent for _create, but prevent issues with incompatible signature…
	protected function _process_create(\DataIter $iter, FormInterface $form)
	{
		// Huh, why are we checking again? Didn't we already check in the run_create() method?
		// Well, yes, but sometimes a policy is picky about how you fill in the data!
		if (!\get_policy($iter)->user_can_create($iter))
			throw new \UnauthorizedException('You are not allowed to create this DataIter according to the policy.');

		$id = $this->model->insert($iter);

		$iter->set_id($id);

		return true;
	}

	// Equivalent for _update, but prevent issues with incompatible signature…
	protected function _process_update(\DataIter $iter, FormInterface $form)
	{
		return $this->model->update($iter) > 0;
	}

	// Equivalent for _delete, but prevent issues with incompatible signature…
	protected function _process_delete(\DataIter $iter)
	{
		return $this->model->delete($iter) > 0;
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
			->add('submit', SubmitType::class, ['label' => 'Delete'])
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

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_process_create($iter, $form))
				$success = true;
			else
				$form->addError(new FormError(__('Something went wrong while processing the form.')));

		return $this->view()->render_create($iter, $form, $success);
	}

	public function run_update(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_update($iter))
			throw new \UnauthorizedException('You are not allowed to edit this ' . get_class($iter) . '.');

		$success = false;

		$form = $this->get_form($iter);

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_process_update($iter, $form))
				$success = true;
			else
				$form->addError(new FormError(__('Something went wrong while processing the form.')));

		return $this->view()->render_update($iter, $form, $success);
	}

	public function run_delete(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_delete($iter))
			throw new \UnauthorizedException('You are not allowed to delete this ' . get_class($iter) . '.');

		$success = false;

		$form = $this->get_delete_form($iter);

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_process_delete($iter))
				$success = true;

		return $this->view()->render_delete($iter, $form, $success);
	}
}
