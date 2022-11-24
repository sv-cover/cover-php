<?php
namespace App\Controller;

require_once 'src/framework/member.php';
require_once 'src/framework/validate.php';
require_once 'src/framework/controllers/Controller.php';

use App\Form\Type\SignUpFormType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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

	public function get_delete_form(\DataIter $iter = null)
	{
		$form = $this->createFormBuilder($iter)
			->add('submit', SubmitType::class, ['label' => 'Delete'])
			->getForm();
		$form->handleRequest($this->get_request());
		return $form;
	}

	public function get_field_form()
	{
		// Field form is not initiated with an iter for data, as field_type is needed to create an iter.
		$form = $this->createFormBuilder()
			->add('field_type', ChoiceType::class, [
				'choices' => array_flip(array_map(
					function($type) { return $type['label']; },
					$this->field_model->field_types
				)),
			])
			->add('submit', SubmitType::class, ['label' => 'Add field'])
			->getForm();
		$form->handleRequest($this->get_request());
		return $form;
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
		$iter = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($iter))
			throw new \UnauthorizedException();

		$form = $this->createFormBuilder(null, ['csrf_token_id' => 'form_' . $iter['id'] . '_delete_entries'])
			->add('entries', ChoiceType::class, [
				'expanded' => true,
				'multiple' => true,
				'choices' => $iter->get_entries(),
				'choice_label' => function ($entity) {
					return $entity['id'];
				},
				'choice_value' => function ($entity) {
					return $entity['id'];
				},
			])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
			foreach ($form->get('entries')->getData() as $entry)
				if (get_policy($this->entry_model)->user_can_delete($entry))
					$this->entry_model->delete($entry);

		return $this->view->redirect($this->generate_url('signup', ['view' => 'list_entries', 'form' => $iter['id']]));
	}

	public function run_create_entry()
	{
		$iter = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_read($iter))
			throw new \UnauthorizedException('You cannot access this form.');

		if ($this->get_parameter('prefill', 'true') === 'false')
			$entry = $iter->new_entry(null);
		else
			$entry = $iter->new_entry(get_identity()->member());

		if (!get_policy($this->entry_model)->user_can_create($entry))
			throw new \UnauthorizedException('You cannot create new entries for this form.');

		$form = $iter->get_form($entry);
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			if (!empty($form->get('member_id')->getData()))
				$entry['member_id'] = (int) $form->get('member_id')->getData();

			// Process the posted values. This will delegate all data handling to the classes
			// in src/fields/*.php
			$entry->process($form);
			$this->entry_model->insert($entry);

			try {
				if (!empty($entry['member_id']) && $iter['agenda_item']) {
					$email = parse_email_object("signup_confirmation.txt", ['entry' => $entry]);
					$email->send($entry['member']['email']);
				}
			} catch (\Exception $e) {
				// Catch it, but it is not important for the rest of the process.
				sentry_report_exception($e);
			}

			if (!empty($form->get('return_path')->getData()))
				return $this->view->redirect($form->get('return_path')->getData());

			// Redirect admins back to the entry index
			if (get_policy($iter)->user_can_update($iter))
				return $this->view->redirect($this->generate_url('signup', ['view' => 'list_entries', 'form' => $iter['id']]));

			return $this->view->render('entry_form_success.twig', [
				'iter' => $iter,
				'entry' => $entry,
				'is_modal' => $this->get_parameter('action', '') === 'modal',
			]);
		}

		return $this->view->render('entry_form.twig', [
			'form' => $form->createView(),
			'iter' => $iter,
			'entry' => $entry,
			'is_modal' => $this->get_parameter('action', '') === 'modal',
		]);
	}

	public function run_update_entry()
	{
		$entry = $this->entry_model->get_iter($_GET['entry']);

		$iter = $entry['form'];

		if (!get_policy($this->form_model)->user_can_read($iter))
			throw new \UnauthorizedException('You cannot access this form.');

		if (!get_policy($this->entry_model)->user_can_read($entry))
			throw new \UnauthorizedException('You cannot access this entry.');

		$form = $iter->get_form($entry);
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			if (!get_policy($this->entry_model)->user_can_update($entry))
				throw new \UnauthorizedException('You cannot update this entry.');
			
			// Process the posted values. This will delegate all data handling to the classes
			// in src/fields/*.php
			$entry->process($form);
			$this->entry_model->insert($entry);

			// Redirect admins back to the entry index
			if (get_policy($iter)->user_can_update($iter))
				return $this->view->redirect($this->generate_url('signup', ['view' => 'list_entries', 'form' => $iter['id']]));

			return $this->view->render('entry_form_success.twig', [
				'iter' => $iter,
				'entry' => $entry,
				'is_modal' => $this->get_parameter('action', '') === 'modal',
			]);
		}

		return $this->view->render('entry_form.twig', [
			'form' => $form->createView(),
			'iter' => $iter,
			'entry' => $entry,
			'is_modal' => $this->get_parameter('action', '') === 'modal',
		]);
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

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_process_update($this->form_model, $iter))
				$success = true;

		return $this->view->render('update_form_form.twig',  [
			'iter' => $iter,
			'form' => $form->createView(),
			'field_form' => $this->get_field_form()->createView(),
		]);
	}

	public function run_delete_form()
	{
		$iter = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_delete($iter))
			throw new \UnauthorizedException('You cannot delete this form.');

		$form = $this->get_delete_form($iter);

		if ($form->isSubmitted() && $form->isValid())
			if ($this->form_model->delete($iter))
				return $this->view->redirect($this->generate_url('signup', ['view' => 'list_forms']));

		return $this->view->render('delete_form.twig', ['form' => $form->createView(), 'iter' => $iter]);
	}

	public function run_create_form_field()
	{
		$iter = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($iter))
			throw new \UnauthorizedException('You cannot update this form.');

		$form = $this->get_field_form();

		if ($form->isSubmitted() && $form->isValid())
		{
			$field = $iter->new_field($form->get('field_type')->getData());
			$this->field_model->insert($field);

			if (isset($_GET['action']) && $_GET['action'] === 'add')
				return $this->view->render('single_field.twig', ['field' => $field, 'form' => $iter]);
		}

		return $this->view->redirect($this->generate_url('signup', ['view' => 'update_form', 'form' => $iter['id']]));
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
		$iter = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($iter))
			throw new \UnauthorizedException('You cannot update this form.');

		$field = $this->field_model->find_one([
			'id' => $_GET['field'], 
			'form_id' => $iter['id']
		]);

		if ($field === null)
			throw new \NotFoundException('Field not found.');

		$form = $this->get_delete_form($field);

		if ($form->isSubmitted() && $form->isValid())
		{
			$this->field_model->delete($field);
			return $this->view->redirect($this->generate_url('signup', [
				'view' => 'restore_form_field',
				'form' => $iter['id'],
				'field' => $field['id']
			]));
		}

		return $this->view->render('delete_field.twig', [
			'form' => $form->createView(),
			'iter' => $iter,
			'field' => $field,
		]);
	}

	public function run_restore_form_field()
	{
		$iter = $this->form_model->get_iter($_GET['form']);

		if (!get_policy($this->form_model)->user_can_update($iter))
			throw new \UnauthorizedException('You cannot update this form.');

		$field = $this->field_model->find_one([
			'id' => $_GET['field'],
			'form_id' => $iter['id'],
			'deleted' => true
		]);

		if ($field === null)
			throw new \NotFoundException('Field not found.');

		$form = $this->createFormBuilder($field)
			->add('submit', SubmitType::class, ['label' => 'Restore'])
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
		{
			$this->field_model->restore($field);
			return $this->view->redirect($this->generate_url('signup', [
				'view' => 'update_form',
				'form' => $iter['id']
			]));
		}

		return $this->view->render('restore_field.twig', [
			'form' => $form->createView(),
			'iter' => $iter,
			'field' => $field,
		]);
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
