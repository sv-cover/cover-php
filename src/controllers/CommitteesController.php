<?php
namespace App\Controller;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

use App\Form\CommitteeType;
use App\Form\DataTransformer\IntToBooleanTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;

class CommitteesController extends \ControllerCRUDForm
{	
	protected $_var_id = 'commissie';

	protected $view_name = 'committees';

	protected $form_type = CommitteeType::class;

	public $mode;

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelCommissie');
		
		parent::__construct($request, $router);
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
		{
			$parameters[$this->_var_id] = $iter['login'];

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}

		return $this->generate_url('committees', $parameters);
	}

	public function new_iter()
	{
		/* Set intial values in form (note the difference between an initial value and empty_data) */
		return $this->model->new_iter(['type' => \DataModelCommissie::TYPE_COMMITTEE]);
	}

	protected function _process_create(\DataIter $iter, FormInterface $form)
	{
		if (!parent::_process_create($iter, $form))
			return false;

		$members = $form['members']->getData();
		if (!empty($members))
			$this->model->set_members($iter, $members);

		return true;
	}

	protected function _process_update(\DataIter $iter, FormInterface $form)
	{
		if (!parent::_process_update($iter, $form))
			return false;

		$members = $form['members']->getData();
		if (!empty($members))
			$this->model->set_members($iter, $members);

		return true;
	}

	protected function _process_delete(\DataIter $iter)
	{
		// Some committees already have pages etc. We will mark the committee as hidden.
		// That way they remain in the history of Cover and could, if needed, be reactivated.
		$iter['hidden'] = true;

		// We'll also remove all its members at least
		$iter['members'] = [];

		return $this->model->update($iter);
	}

	protected function _read($id)
	{
		if (!ctype_digit($id))
			return $this->model->get_from_name($id);
		else
			return parent::_read($id);
	}

	/**
	 * Override ControllerCRUD::run_index to also restrict the model to the same type as the iter.
	 */ 
	public function run_index()
	{
		$committees = $this->model->get(\DataModelCommissie::TYPE_COMMITTEE);			
		$working_groups = $this->model->get(\DataModelCommissie::TYPE_WORKING_GROUP);

		$iters = [
			'committees' => array_filter($committees, array(get_policy($this->model), 'user_can_read')),
			'working_groups' => array_filter($working_groups, array(get_policy($this->model), 'user_can_read')),
		];

		return $this->view()->render_index($iters);
	}

	/**
	 * Override ControllerCRUD::run_read to also restrict the model to the same type as the iter.
	 */ 
	public function run_read(\DataIter $iter)
	{
		if ($iter['hidden'])
			throw new \NotFoundException('This committee/group is no longer available');

		if (!get_policy($this->model)->user_can_read($iter))
			throw new \UnauthorizedException('You are not allowed to read this ' . get_class($iter) . '.');

		$iters = $this->model->get($iter['type']);

		return $this->view()->render_read($iter, [
			'iters' => $iters,
			'interest_reported' => !empty($_GET['interest_reported'])
		]);
	}

	public function run_update(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_update($iter))
			throw new \UnauthorizedException('You are not allowed to edit this ' . get_class($iter) . '.');

		$success = false;

		$builder = get_form_factory()->createBuilder($this->form_type, $iter, ['mapped' => false]);

		// Add field to reactivate deactivated groups
		if (!empty($iter['hidden'])) {
			$builder->add('hidden', CheckboxType::class, [
				'label' => __('This group is deactivated.'),
				'help' => __('Uncheck this box and submit to reactivate.'),
				'required' => false,
			]);
			$builder->get('hidden')->addModelTransformer(new IntToBooleanTransformer());
		}

		// Populate members field
		// TODO: this is terribly inefficiënt
		$members = array_map(function($member) { return ['member_id' => $member['id'], 'functie' => $member['functie']];}, $iter->get_members());
		$builder->get('members')->setData($members);

		$form = $builder->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
			if ($this->_process_update($iter, $form))
				$success = true;
			else
				$form->addError(new FormError(__('Something went wrong while processing the form.')));

		return $this->view()->render_update($iter, $form, $success);
	}

	public function run_show_interest(\DataIter $iter)
	{
		if (!get_identity()->is_member())
			throw new \UnauthorizedException('Only members can apply for a committee');

		if (!get_policy($this->model)->user_can_read($iter))
			throw new \UnauthorizedException('You are not allowed to read this ' . get_class($iter) . '.');

		$form = $this->createFormBuilder($iter, ['csrf_token_id' => 'committee_interest_' . $iter['id']])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			if (get_config_value('path_to_committee_interest_log'))
				error_log(sprintf("%s - %s (%d) is interested in %s.\n", date('c'), get_identity()->member()['full_name'], get_identity()->member()['id'], $iter['naam']), 3, get_config_value('path_to_committee_interest_log'));

			$mail = parse_email_object("interst_in_committee.txt", [
				'committee' => $iter,
				'member' => get_identity()->member()
			]);
			$mail->send('intern@svcover.nl');

			return $this->view->redirect($this->generate_url('committees', ['view' => 'read', $this->_var_id => $iter['login'], 'interest_reported' => true]));
		}

		return $this->view->redirect($this->generate_url('committees', ['view' => 'read', $this->_var_id => $iter['login']]));
	}

	/**
	 * The Thrash! All (including deleted) committees/groups/others/etc
	 */
	public function run_archive()
	{
		$iters = $this->model->get(null, true);

		return $this->view->render_archive($iters);
	}

	public function run_slide()
	{
		// for debugging purposes
		if (isset($_GET['commissie'])) {
			$committee = $this->model->get_from_name($_GET['commissie']);
		} else {
			// Pick a random commissie
			$committee = $this->model->get_random(\DataModelCommissie::TYPE_COMMITTEE, true);
		}
		return $this->view->render_slide($committee);
	}

	/**
	 * Override the default ControllerCRUD::run_impl to allow either ?commissie= and ?id=.
	 */
	protected function run_impl()
	{
		// Support for old urls
		if (isset($_GET['id']) && !isset($_GET['commissie']))
			$_GET['commissie'] = $_GET['id'];

		return parent::run_impl();
	}
}
