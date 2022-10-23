<?php
namespace App\Controller;

require_once 'src/framework/member.php';
require_once 'src/framework/validate.php';
require_once 'src/framework/controllers/Controller.php';

use App\Form\Type\SignUpFormType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SignUpFormsController extends \Controller
{
	protected $view_name = 'signup';

	public function __construct($request, $router)
	{
		$this->form_model = get_model('DataModelSignUpForm');

		$this->field_model = get_model('DataModelSignUpField');

		$this->entry_model = get_model('DataModelSignUpEntry');

		parent::__construct($request, $router);
	}

	protected function run_impl()
	{
		$view = isset($_GET['view']) ? $_GET['view'] : 'list_forms';

		if (method_exists($this, 'run_' . $view))
			return call_user_func([$this, 'run_' . $view]);
		else
			throw new \NotFoundException('No such view');
	}

	public function run_export_entries()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new \UnauthorizedException();

		$entries = array_filter($form['entries'], function($entry) {
			return get_policy($entry)->user_can_read($entry);
		});

		$rows = array_map(function($entry) {
			return $entry->export();
		}, $entries);

		$headers = $form->get_column_labels();

		$this->view->render_csv($rows, array_values($headers), sprintf('signup-form-%d-%s.csv', $form['id'], date('ymd-his')));
	}

	public function run_list_entries()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new \UnauthorizedException();

		return $this->view->render('list_entries.twig', compact('form'));
	}

	public function run_delete_entries()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new \UnauthorizedException();

		if ($this->_form_is_submitted('delete_entries', $form) && !empty($_POST['entries']))
			foreach ($_POST['entries'] as $entry_id)
				if ($entry = $this->entry_model->find_one(['form_id' => $form['id'], 'id' => $entry_id]))
					if (get_policy($this->entry_model)->user_can_delete($entry))
						$this->entry_model->delete($entry);

		return $this->view->redirect($this->generate_url('signup', ['view' => 'list_entries', 'form' => $form['id']]));
	}

	public function run_create_entry()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new \UnauthorizedException('You cannot access this form.');

		$entry = $form->new_entry(null);

		if (!get_policy($this->entry_model)->user_can_create($entry))
			throw new \UnauthorizedException('You cannot create new entries for this form.');

		$success = false;

		$is_modal = isset($_GET['action']) && $_GET['action'] === 'modal';

		if ($this->_form_is_submitted('create_entry', $form)) {
			// If the form submitted a member-id (i.e. a logged-in member filled it in) then
			// check whether that member is indeed the logged-in member and assign the entry
			// to them if so.
			if (!empty($_POST['member_id']) && get_identity()->get('id') == $_POST['member_id'])
				$entry['member_id'] = (int) $_POST['member_id'];

			// Process the posted values. This will delegate all data handling to the classes
			// in src/fields/*.php
			if ($entry->process($_POST)) {
				$this->entry_model->insert($entry);
				$success = true;
			}

			try {
				if ($success && !empty($entry['member_id']) && $form['agenda_item']) {
					$email = parse_email_object("signup_confirmation.txt", ['entry' => $entry]);
					$email->send($entry['member']['email']);
				}
			} catch (\Exception $e) {
				// Catch it, but it is not important for the rest of the process.
				sentry_report_exception($e);
			}

			// Redirect submissions from elsewhere back to their return-path
			if ($success && !empty($_POST['return-path']))
				return $this->view->redirect($_POST['return-path']);

			// Redirect admins back to the entry index
			if ($success && get_policy($form)->user_can_update($form))
				return $this->view->redirect($this->generate_url('signup', ['view' => 'list_entries', 'form' => $form['id']]));

			// and everyone else will just see the form with a success message
		}

		return $this->view->render('entry_form.twig', compact('form', 'entry', 'success', 'is_modal'));
	}

	public function run_update_entry()
	{
		$entry = $this->entry_model->get_iter($_GET['entry']);

		$form = $entry['form'];

		if (!get_policy($this->form_model)->user_can_read($form))
			throw new \UnauthorizedException('You cannot access this form.');

		if (!get_policy($this->entry_model)->user_can_read($entry))
			throw new \UnauthorizedException('You cannot access this entry.');

		$success = false;

		$is_modal = isset($_GET['action']) && $_GET['action'] === 'modal';

		if ($this->_form_is_submitted('update_entry', $entry)) {
			if (!get_policy($this->entry_model)->user_can_update($entry))
				throw new \UnauthorizedException('You cannot update this entry.');
		
			if ($entry->process($_POST)) {
				$this->entry_model->update($entry);
				$success = true;
			}
			
			// Redirect admins back to the entry index
			if ($success && get_policy($form)->user_can_update($form))
				return $this->view->redirect($this->generate_url('signup', ['view' => 'list_entries', 'form' => $form['id']]));

			// and everyone else will just see the form with a success message
		}

		return $this->view->render('entry_form.twig', compact('form', 'entry', 'success', 'is_modal'));
	}

	public function run_list_forms()
	{
		if (!get_identity()->get('committees'))
			throw new \UnauthorizedException('Only committee members may create and manage forms.');

		if (get_identity()->member_in_committee(COMMISSIE_BESTUUR) || get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR))
			$forms = $this->form_model->get();
		else
			$forms = $this->form_model->find(['committee_id__in' => get_identity()->get('committees')]);

		return $this->view->render('list_forms.twig', compact('forms'));
	}

	public function run_create_form()
	{
		$iter = $this->new_form();

		if (!get_policy($this->form_model)->user_can_create($iter))
			throw new \UnauthorizedException('You cannot create new forms.');

		if (isset($_GET['agenda'])) {
			$iter['agenda_id'] = $_GET['agenda'];
			// agenda_item will be automatically queried based on the previously set agenda_id
			$iter['committee_id'] = $iter['agenda_item']['committee_id'];
		}

		$form = $this->createForm(SignUpFormType::class, $iter, ['mapped' => false]);
		$form->add('template', ChoiceType::class, [
            'label' => __('Template'),
            'choices' => [
            	__('Sign-up form for a paid activitee') => 'paid_activity',
            ],
            'help' => __('Choose a template to start with a set of predefined fields.'),
            'placeholder' => __('Empty form'),
            'mapped' => false,
            'required' => false,
        ]);
		$form->handleRequest($this->get_request());

		$success = false;

		if ($form->isSubmitted() && $form->isValid()) {
			if ($this->_process_create($this->form_model, $iter))
				$success = true;

			if ($success && !empty($form->get('template')->getData()))
				$this->_init_form_with_template($iter, $form->get('template')->getData());
		}

		if ($success)
			return $this->view->redirect($this->generate_url('signup', ['view' => 'update_form', 'form' => $iter['id']]) . '#signup-form-fields');
		else
			return $this->view->render('create_form_form.twig', [
				'iter' => $iter,
				'form' => $form->createView(),
			]);
	}

	public function run_update_form()
	{
		$iter = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($iter))
			throw new \UnauthorizedException('You cannot update this form.');

		$form = $this->createForm(SignUpFormType::class, $iter, ['mapped' => false]);
		$form->handleRequest($this->get_request());

		$success = false;

		$errors = new \ErrorSet();

		// if ($this->_form_is_submitted('update_form', $form))
		if ($form->isSubmitted() && $form->isValid())
			if ($this->_process_update($this->form_model, $iter))
				$success = true;
			// if ($this->_update($this->form_model, $form, $_POST, $errors))

		return $this->view->render('update_form_form.twig',  [
			'iter' => $iter,
			'form' => $form->createView(),
		]);
	}

	public function run_delete_form()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_delete($form))
			throw new \UnauthorizedException('You cannot delete this form.');

		if ($this->_form_is_submitted('delete_form', $form))
			if ($this->form_model->delete($form))
				return $this->view->redirect($this->generate_url('signup', ['view' => 'list_forms']));

		return $this->view->render('delete_form.twig', compact('form'));
	}

	public function run_create_form_field()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new \UnauthorizedException('You cannot update this form.');

		if ($this->_form_is_submitted('create_form_field', $form)) {
			$field = $form->new_field($_POST['field_type']);
			$this->field_model->insert($field);

			if (isset($_GET['action']) && $_GET['action'] === 'add')
				return $this->view->render('single_field.twig', ['field' => $field, 'form' => $form, 'errors' => new \ErrorSet()]);
		}

		return $this->view->redirect($this->generate_url('signup', ['view' => 'update_form', 'form' => $form['id']]));
	}

	public function run_update_form_field()
	{
		$iter = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($iter))
			throw new \UnauthorizedException('You cannot update this form.');

		$field = array_find($iter['fields'], function($field) { return $field['id'] == $_GET['field']; });

		if (!$field)
			throw new \NotFoundException('Field not part of this form');

		$form = $field->get_configuration_form();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid() && $field->process_configuration($form))
		{
			$this->field_model->update($field);
			return $this->view->redirect($this->generate_url('signup', ['view' => 'update_form', 'form' => $iter['id']]));
		}

		return $this->view->render('update_form_field.twig', [
			'iter' => $iter,
			'field' => $field,
			'form' => $form->createView(),
		]);
	}

	public function run_delete_form_field()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new \UnauthorizedException('You cannot update this form.');

		$field = $this->field_model->find_one([
			'id' => $_GET['field'], 
			'form_id' => $form['id']
		]);

		if ($field === null)
			throw new \NotFoundException('Field not found.');

		if ($this->_form_is_submitted('delete_form_field', $form, $field))
		{
			$this->field_model->delete($field);
			return $this->view->redirect($this->generate_url('signup', [
				'view' => 'restore_form_field',
				'form' => $form['id'],
				'field' => $field['id']
			]));
		}

		return $this->view->render('delete_field.twig', compact('form', 'field'));
	}

	public function run_restore_form_field()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new \UnauthorizedException('You cannot update this form.');

		$field = $this->field_model->find_one([
			'id' => $_GET['field'],
			'form_id' => $form['id'],
			'deleted' => true
		]);

		if ($field === null)
			throw new \NotFoundException('Field not found.');

		if ($this->_form_is_submitted('restore_form_field', $form, $field))
		{
			$this->field_model->restore($field);
			return $this->view->redirect($this->generate_url('signup', [
				'view' => 'update_form',
				'form' => $form['id']
			]));
		}

		return $this->view->render('restore_field.twig', compact('form', 'field'));
	}

	public function run_update_form_field_order()
	{
		$form = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($form))
			throw new \UnauthorizedException('You cannot update this form.');

		$fields = $form['fields'];

		$indexes = array_map(function($field) {
			return array_search($field['id'], $_POST['order']);
		}, $fields);

		array_multisort($indexes, $fields);

		$this->field_model->update_order($fields);

		return $this->view->redirect($this->generate_url('signup', ['view' => 'update_form', 'form' => $form['id']]));
	}

	private function _create(\DataModel $model, \DataIter $iter, array $input, \ErrorSet $errors)
	{
		$data = validate_dataiter($iter, $input, $errors);

		if ($data === false)
			return false;

		$iter->set_all($data);

		// Huh, why are we checking again? Didn't we already check in the run_create() method?
		// Well, yes, but sometimes a policy is picky about how you fill in the data!
		if (!get_policy($model)->user_can_create($iter))
			throw new \UnauthorizedException('You cannot create new forms.');

		$id = $model->insert($iter);

		$iter->set_id($id);

		return true;
	}

	protected function _process_create(\DataModel $model, \DataIter $iter)
	{
		// Huh, why are we checking again? Didn't we already check in the run_create() method?
		// Well, yes, but sometimes a policy is picky about how you fill in the data!
		if (!\get_policy($iter)->user_can_create($iter))
			throw new \UnauthorizedException('You are not allowed to create this DataIter according to the policy.');

		$id = $model->insert($iter);

		$iter->set_id($id);

		return true;
	}

	private function _update(\DataModel $model, \DataIter $iter, array $input, \ErrorSet $errors)
	{
		$data = validate_dataiter($iter, $input, $errors);

		$iter->set_all($data);

		if (!get_policy($model)->user_can_update($iter))
			throw new \UnauthorizedException('You cannot update this form');

		$model->update($iter);

		return true;
	}

	protected function _process_update(\DataModel $model, \DataIter $iter)
	{
		return $model->update($iter) > 0;
	}

	public function available_templates()
	{
		return [
			'paid_activity' => __('Sign-up form for a paid activitee')
		];
	}

	private function _init_form_with_template(\DataIter $form, $template)
	{
		if ($template == 'paid_activity')
		{
			$this->field_model->db->beginTransaction();

			$this->field_model->insert($form->new_field('editable', function($widget) {
				$widget->content = "[h2]Sign up now![/h2]\nShort description of why you need to sign up and what you will receive in return.";
			}));

			$this->field_model->insert($form->new_field('name', function($widget) {
				$widget->required = true;
			}));

			$this->field_model->insert($form->new_field('editable', function($widget) {
				$widget->content = "We also need your email address to contact you, and address and bank account details to make a direct debit for you.";
			}));

			$this->field_model->insert($form->new_field('email', function($widget) {
				$widget->required = true;
			}));

			$this->field_model->insert($form->new_field('address', function($widget) {
				$widget->required = true;
			}));

			$this->field_model->insert($form->new_field('bankaccount', function($widget) {
				$widget->required = true;
			}));

			$this->field_model->insert($form->new_field('checkbox', function($widget) {
				$widget->required = true;
				$widget->description = 'I allow Cover to deduct €x,xx from my bank account.';
			}));

			$this->field_model->db->commit();
		}
	}

	public function new_form()
	{
		$iter = $this->form_model->new_iter();

		// Default to created_on = now
		$iter['created_on'] = new \DateTime('now');

		return $iter;
	}
}
