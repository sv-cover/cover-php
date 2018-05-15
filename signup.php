<?php

require_once 'include/init.php';
require_once 'include/controllers/Controller.php';
require_once 'include/validate.php';

class ControllerSignUpForms extends Controller
{
	public function __construct()
	{
		$this->form_model = get_model('DataModelSignUpForm');

		$this->entry_model = get_model('DataModelSignUpEntry');

		$this->view = View::byName('signup', $this);
	}

	protected function run_impl()
	{
		$view = isset($_GET['view']) ? $_GET['view'] : 'list_forms';

		if (method_exists($this, 'run_' . $view))
			return call_user_func([$this, 'run_' . $view]);
		else
			throw new NotFoundException('No such view');
	}

	public function run_list_entries()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new UnauthorizedException();

		return $this->view->render('list_entries.twig', compact('form'));
	}

	public function run_create_entry()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new UnauthorizedException();

		$entry = $form->new_entry(get_identity()->member());

		if (!get_policy($this->entry_model)->user_can_create($entry))
			throw new UnauthorizedException();

		$success = false;

		$errors = [];

		if ($this->_form_is_submitted('create_entry', $form))
		{
			$field_values = [];

			foreach ($form['fields'] as $field)
				$field_values[$field['id']] = $field->process($_POST, $errors);

			if (count($errors) === 0) {
				$this->entry_model->insert($entry);
				$entry->set_field_values($field_values);
				$success = true;
			}
		}

		return $this->view->render('entry_form.twig', compact('form', 'entry', 'success', 'errors'));
	}

	public function run_list_forms()
	{
		$forms = $this->form_model->find(['committee_id__in' => get_identity()->get('committees')]);

		return $this->view->render('list_forms.twig', compact('forms'));
	}

	public function run_create_form()
	{
		$form = $this->new_form();

		if (!get_policy($this->form_model)->user_can_create($form))
			throw new UnauthorizedException('You are not allowed to create new forms');

		$success = false;

		$errors = [];

		if ($this->_form_is_submitted('create_form'))
			if ($this->_create($this->form_model, $form, $_POST, $errors))
				$success = true;

		return $this->view->render('form_form.twig', compact('form', 'success', 'errors'));
	}

	public function run_update_form()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new UnauthorizedException();

		$success = false;

		$errors = [];

		if ($this->_form_is_submitted('update_form', $form))
			if ($this->_update($this->form_model, $form, $_POST, $errors))
				$success = true;

		return $this->view->render('form_form.twig', compact('form', 'success', 'errors'));
	}

	private function _create(DataModel $model, DataIter $iter, array $input, array &$errors)
	{
		$data = validate_dataiter($iter, $input, $errors);

		if ($data === false)
			return false;

		$iter->set_all($data);

		// Huh, why are we checking again? Didn't we already check in the run_create() method?
		// Well, yes, but sometimes a policy is picky about how you fill in the data!
		if (!get_policy($iter)->user_can_create($iter))
			throw new UnauthorizedException('You are not allowed to create this DataIter according to the policy.');

		$id = $model->insert($iter);

		$iter->set_id($id);

		return true;
	}

	public function new_form()
	{
		return $this->form_model->new_iter();
	}
}

$controller = new ControllerSignUpForms();
$controller->run();
