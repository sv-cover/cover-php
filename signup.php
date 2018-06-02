<?php

require_once 'include/init.php';
require_once 'include/controllers/Controller.php';
require_once 'include/validate.php';

class ControllerSignUpForms extends Controller
{
	public function __construct()
	{
		$this->form_model = get_model('DataModelSignUpForm');

		$this->field_model = get_model('DataModelSignUpField');

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

	public function run_export_entries()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new UnauthorizedException();

		$entries = array_filter($form['entries'], function($entry) {
			return get_policy($entry)->user_can_read($entry);
		});

		$rows = array_map(function($entry) {
			return $entry->export();
		}, $entries);

		$this->view->render_csv($rows);
	}

	public function run_list_entries()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new UnauthorizedException();

		return $this->view->render('list_entries.twig', compact('form'));
	}

	public function run_delete_entries()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new UnauthorizedException();

		if ($this->_form_is_submitted('delete_entries', $form))
			foreach ($_POST['entries'] as $entry_id)
				if ($entry = $this->entry_model->find_one(['form_id' => $form['id'], 'id' => $entry_id]))
					if (get_policy($this->entry_model)->user_can_delete($entry))
						$this->entry_model->delete($entry);

		return $this->view->redirect($this->link(['view' => 'list_entries', 'form' => $form['id']]));
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

		if ($this->_form_is_submitted('create_entry', $form)) {
			if ($entry->process($_POST)) {
				$this->entry_model->insert($entry);
				$success = true;
			}
		}

		return $this->view->render('entry_form.twig', compact('form', 'entry', 'success'));
	}

	public function run_update_entry()
	{
		$entry = $this->entry_model->get_iter($_GET['entry']);

		$form = $entry['form'];

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new UnauthorizedException('You cannot access this form.');

		if (!get_policy($this->entry_model)->user_can_update($entry))
			throw new UnauthorizedException('You cannot update this entry.');

		$success = false;

		if ($this->_form_is_submitted('update_entry', $entry)) {
			if ($entry->process($_POST)) {
				$this->entry_model->update($entry);
				$success = true;
			}
		}

		return $this->view->render('entry_form.twig', compact('form', 'entry', 'success'));
	}

	public function run_list_forms()
	{
		if (!get_identity()->get('committees'))
			throw new UnauthorizedException('Only committee members may create and manage forms.');

		$forms = $this->form_model->find(['committee_id__in' => get_identity()->get('committees')]);

		return $this->view->render('list_forms.twig', compact('forms'));
	}

	public function run_create_form()
	{
		$form = $this->new_form();

		if (!get_identity()->get('committees'))
			throw new UnauthorizedException('Only committee members may create and manage forms.');

		if (!get_policy($this->form_model)->user_can_create($form))
			throw new UnauthorizedException('You cannot create new forms.');

		$success = false;

		$errors = new ErrorSet();

		if ($this->_form_is_submitted('create_form'))
			if ($this->_create($this->form_model, $form, $_POST, $errors))
				$success = true;

		return $this->view->render('form_form.twig', compact('form', 'success', 'errors'));
	}

	public function run_update_form()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new UnauthorizedException('You cannot update this form.');

		$success = false;

		$errors = new ErrorSet();

		if ($this->_form_is_submitted('update_form', $form))
			if ($this->_update($this->form_model, $form, $_POST, $errors))
				$success = true;

		return $this->view->render('form_form.twig', compact('form', 'success', 'errors'));
	}

	public function run_create_form_field()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new UnauthorizedException('You cannot update this form.');

		if ($this->_form_is_submitted('create_form_field', $form))
			$this->field_model->insert($form->new_field($_POST['field_type']));

		return $this->view->redirect($this->link(['view' => 'update_form', 'form' => $form['id']]));
	}

	public function run_update_form_field()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new UnauthorizedException('You cannot update this form.');

		$field = array_find($form['fields'], function($field) { return $field['id'] == $_GET['field']; });

		if (!$field)
			throw new NotFoundException('Field not part of this form');

		$success = false;

		$errors = new ErrorSet();

		if ($this->_form_is_submitted('update_form_field', $form, $field))
		{
			if ($_POST['action'] == 'update')
				if ($field->process_configuration($_POST, $errors->namespace($field['id'])))
					$this->field_model->update($field);
				else
					return $this->view->render('form_form.twig', compact('form', 'success', 'errors'));
			
			if ($_POST['action'] == 'delete')
				$this->field_model->delete($field);
		}

		return $this->view->redirect($this->link(['view' => 'update_form', 'form' => $form['id']]));
	}

	public function run_update_form_field_order()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new UnauthorizedException('You cannot update this form.');

		$fields = $form['fields'];

		$indexes = array_map(function($field) {
			return array_search($field['id'], $_POST['order']);
		}, $fields);

		array_multisort($indexes, $fields);

		$this->field_model->update_order($fields);

		return $this->view->redirect($this->link(['view' => 'update_form', 'form' => $form['id']]));
	}

	private function _create(DataModel $model, DataIter $iter, array $input, ErrorSet $errors)
	{
		$data = validate_dataiter($iter, $input, $errors);

		if ($data === false)
			return false;

		$iter->set_all($data);

		// Huh, why are we checking again? Didn't we already check in the run_create() method?
		// Well, yes, but sometimes a policy is picky about how you fill in the data!
		if (!get_policy($iter)->user_can_create($iter))
			throw new UnauthorizedException('You cannot create new forms.');

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
