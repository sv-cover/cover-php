<?php
namespace App\Controller;

use App\Form\PollType;
use App\Form\DataTransformer\StringToDateTimeTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints as Assert;

require_once 'src/framework/controllers/ControllerCRUD.php';

class PollsController extends \ControllerCRUD
{
	CONST PAGE_SIZE = 10;

	protected $view_name = 'polls';
	protected $form_type = PollType::class;

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelPoll');

		parent::__construct($request, $router);

	}

	public function new_iter()
	{
		/* Set intial values in form (note the difference between an initial value and empty_data) */
		return $this->model->new_iter([
			'member_id' => \get_identity()->get('id'),
		]);
	}

	public function path(string $view, \DataIter $iter = null)
	{
		$parameters = [
			'view' => $view,
		];

		if (isset($iter))
			$parameters['id'] = $iter->get_id();

		if ($view === 'index')
			return $this->generate_url('poll.list');


		if ($view === 'create')
			return $this->generate_url('poll.create', $parameters);

		return $this->generate_url('poll', $parameters);
	}

	protected function _create(\DataIter $iter, FormInterface $form)
	{
		if (!parent::_create($iter, $form))
			return false;

		$options = $form['options']->getData();
		if (!empty($options))
			$this->model->set_options($iter, $options);

		return true;
	}

	public function run_index()
	{
		$page = $this->get_parameter('page', 0);
		$page_count = $this->model->count_polls() / $this::PAGE_SIZE;

		if ($page > $page_count)
			throw new \NotFoundException();

		$iters = array_filter(
			$this->model->get_polls($this::PAGE_SIZE, $page * $this::PAGE_SIZE),
			[get_policy($this->model), 'user_can_read']
		);

		return $this->view()->render('index.twig', [
			'iters' => $iters,
			'page' => $page,
			'page_count' => $page_count,
		]);
	}

	public function run_create()
	{
		$iter = $this->new_iter();

		if (!get_auth()->logged_in())
			throw new \UnauthorizedException('You are not allowed to create polls.');

		if (!\get_policy($this->model)->user_can_create($iter))
			return $this->view()->render('no_create.twig');


		$success = false;

		$form = $this->createForm($this->form_type, $iter, ['mapped' => false]);
		if (!\get_identity()->member_in_committee())
			$form->remove('committee_id');
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			if ($this->_create($iter, $form))
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

		if ($form->isSubmitted() && $form->isValid()) {
			$iter['closed_on'] = new \DateTime();
			$this->model->update($iter);

			$next_url = $this->get_parameter('referrer', $this->generate_url('poll.list'));
			return $this->view->redirect($next_url);
		}

		return $this->view->render('confirm_close.twig',  [
			'iter' => $iter,
			'form' => $form->createView(),
		]);
	}


	public function run_reopen(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_close($iter))
			throw new \UnauthorizedException('You are not allowed to re-open this poll.');

		$iter['closed_on'] = null;

		$builder = $this->createFormBuilder($iter)
			->add('closed_on', DateTimeType::class, [
				'label' => __('Closes on'),
				'constraints' => new Assert\Callback([
					'callback' => [PollType::class, 'validate_closed_on'],
				]),
				'widget' => 'single_text',
				'required' => false,
				'help' => __('People can vote until this date. If you provide no date, the poll closes as soon as the next poll is created.'),
			])
			->add('submit', SubmitType::class, ['label' => __('Reopen poll'), 'color' => 'danger']);
		$builder->get('closed_on')->addModelTransformer(new StringToDateTimeTransformer(null, null, 'Y-m-d H:i:s'));
		$form = $builder->getForm();
		$form->handleRequest($this->get_request());

		if ($form->isSubmitted() && $form->isValid()) {
			$this->model->update($iter);

			$next_url = $this->get_parameter('referrer', $this->generate_url('poll.list'));
			return $this->view->redirect($next_url);
		}

		return $this->view->render('confirm_reopen.twig',  [
			'iter' => $iter,
			'form' => $form->createView(),
		]);
	}

	public function run_vote(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_vote($iter)) {
			if (\get_auth()->logged_in())
				throw new \UnauthorizedException('You are not allowed vote!');
			return $this->view->redirect($this->generate_url('login', [
				'referrer' =>  $this->generate_url('poll', ['id' => $iter->get_id()])
			]));
		}

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

	public function run_likes(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_like($iter)) {
			if (\get_auth()->logged_in())
				throw new \UnauthorizedException('You are not allowed like polls!');
			return $this->view->redirect($this->generate_url('login', [
				'referrer' =>  $this->generate_url('poll', ['id' => $iter->get_id()])
			]));
		}

		$action = null;
		$response_json = false;

		$form = $this->createFormBuilder(null, ['csrf_token_id' => 'like_poll_' . $iter->get_id()])
			->add('like', SubmitType::class)
			->add('unlike', SubmitType::class)
			->getForm();
		$form->handleRequest($this->get_request());

		if ($_SERVER["CONTENT_TYPE"] === 'application/json') {
			$response_json = true;
			$json = file_get_contents('php://input');
			$data = json_decode($json);
			if (isset($data->action))
				$action = $data->action;
		} elseif ($form->isSubmitted() && $form->isValid()) {
			$action = $form->get('like')->isClicked() ? 'like' : 'unlike';
		}

		if (get_auth()->logged_in() && isset($action)) {
			try {
				if ($action === 'like')
					\get_model('DataModelPollLike')->like($iter, get_identity()->member());
				elseif ($action === 'unlike')
					\get_model('DataModelPollLike')->unlike($iter, get_identity()->member());
			} catch (\Exception $e) {
				// Don't break duplicate requests
			}
		}

		if ($response_json)
			return $this->view->render_json([
				'liked' => get_auth()->logged_in() && $iter->is_liked_by(get_identity()->member()),
				'likes' => count($iter->get_likes()),
			]);

		$next_url = $this->get_parameter('referrer', $this->generate_url('poll', [
			'id' => $iter->get_id(),
		]));
		return $this->view->redirect($next_url);
	}
}
