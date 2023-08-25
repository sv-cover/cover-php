<?php
namespace App\Controller;

use App\Form\PollType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

class PollsController extends \ControllerCRUDForm
{
	protected $view_name = 'polls';
	protected $form_type = PollType::class;

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelNewPoll');

		parent::__construct($request, $router);

	}

	public function new_iter()
	{
		/* Set intial values in form (note the difference between an initial value and empty_data) */
		return $this->model->new_iter([
			'member_id' => \get_identity()->get('id'),
		]);
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
		{
			$parameters['id'] = $iter->get_id();

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}

		if ($view === 'index')
			return $this->generate_url('poll.list');


		if ($view === 'create')
			return $this->generate_url('poll.create', $parameters);

		return $this->generate_url('poll', $parameters);
	}

	protected function _process_create(\DataIter $iter, FormInterface $form)
	{
		if (!parent::_process_create($iter, $form))
			return false;

		$options = $form['options']->getData();
		if (!empty($options))
			$this->model->set_options($iter, $options);

		return true;
	}

	public function run_create()
	{
		$iter = $this->new_iter();

		if (!\get_policy($this->model)->user_can_create($iter))
			throw new \UnauthorizedException('You are not allowed to create polls.');

		$success = false;

		$form = $this->createForm($this->form_type, $iter, ['mapped' => false]);
		if (!\get_identity()->member_in_committee())
			$form->remove('committee_id');
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			if ($this->_process_create($iter, $form))
				$success = true;
			else
				$form->addError(new FormError(__('Something went wrong while processing the form.')));
		}

		return $this->view()->render_create($iter, $form, $success);
	}

	public function run_update(\DataIter $iter)
	{
		// Updating polls is not allowed, otherwise votes could be misrepresented
		throw new \NotFoundException();
	}


	public function run_close(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_close($iter))
			throw new \UnauthorizedException('You are not allowed to close this poll.');
		$form = $this->createFormBuilder($iter)
			->add('submit', SubmitType::class, ['label' => __('Close poll'), 'color' => 'danger'])
			->getForm();
		$form->handleRequest($this->get_request());
	}


	public function run_reopen(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_close($iter))
			throw new \UnauthorizedException('You are not allowed to re-open this poll.');
	}

	public function run_vote(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_vote($iter))
			throw new \UnauthorizedException('You are not allowed vote!');

		$form = $this->createFormBuilder(null, ['csrf_token_id' => 'vote_poll_' . $iter->get_id()])
			->add('option', ChoiceType::class, [
				'expanded' => true,
				'choices' => $iter['options'],
				'choice_label' => function ($entity) {
					return $entity['option'] ?? 'Unknown';
				},
				'choice_value' => function ($entity) {
					return $entity['id'] ?? '';
				},
			])
			->add('submit', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid())
			$this->model->set_member_vote(
				$form['option']->getData(),
				get_identity()->member()
			);

		$next_url = $this->get_parameter('referrer', $this->generate_url('poll.list'));
		return $this->view->redirect($next_url);
	}
}
