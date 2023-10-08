<?php
namespace App\Controller;

use App\Form\PollCommentType;
use App\Form\DataTransformer\StringToDateTimeTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints as Assert;

require_once 'src/framework/controllers/ControllerCRUDForm.php';

class PollCommentsController extends \ControllerCRUDForm
{
	protected $view_name = 'pollcomments';
	protected $form_type = PollCommentType::class;

	protected $poll = false;

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelPollComment');

		parent::__construct($request, $router);

	}

	public function get_form(\DataIter $iter = null)
	{
		if ($iter->has_id())
			$form = $this->createForm($this->form_type, $iter, ['mapped' => false]);
		else
			$form = $this->createForm($this->form_type, $iter, [
				'mapped' => false,
				'csrf_token_id' => sprintf('poll_%s_comment', $this->get_poll()->get_id()),
			]);
		$form->handleRequest($this->get_request());
		return $form;
	}

	protected function get_poll() {
		if ($this->poll === false)
			$this->poll = \get_model('DataModelPoll')->get_iter($this->get_parameter('poll_id'));
		return $this->poll;
	}

	public function new_iter()
	{
		$iter = parent::new_iter();
		$iter->set('poll_id', $this->get_poll()->get_id());
		$iter->set('member_id', \get_identity()->get('id'));
		return $iter;
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			'view' => $view,
			'poll_id' => $this->get_poll()->get_id()
		];

		if ($view === 'read' || $view === 'index')
			return $this->generate_url('poll', ['id' => $this->get_poll()->get_id()]);

		if (isset($iter))
		{
			$parameters['id'] = $iter->get_id();

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}


		if ($view === 'create')
			return $this->generate_url('poll.comment.create', $parameters);

		return $this->generate_url('poll.comment', $parameters);
	}

	public function run_read(\DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter))
			throw new UnauthorizedException('You are not allowed to read this ' . get_class($iter) . '.');
		return $this->view->redirect($this->generate_url('poll', ['id' => $iter['poll_id']]));
	}

	public function run_likes(\DataIter $iter)
	{
		if (!\get_policy($this->model)->user_can_like($iter)) {
			if (\get_auth()->logged_in())
				throw new \UnauthorizedException('You are not allowed like polls!');
			return $this->view->redirect($this->generate_url('login', [
				'referrer' =>  $this->generate_url('poll', ['id' => $this->get_poll()->get_id()])
			]));
		}

		$action = null;
		$response_json = false;

		$form = $this->createFormBuilder(null, ['csrf_token_id' => 'like_poll_comment_' . $iter->get_id()])
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
					\get_model('DataModelPollCommentLike')->like($iter, get_identity()->member());
				elseif ($action === 'unlike')
					\get_model('DataModelPollCommentLike')->unlike($iter, get_identity()->member());
			} catch (\Exception $e) {
				// Don't break duplicate requests
			}
		}

		if ($response_json)
			return $this->view->render_json([
				'liked' => get_auth()->logged_in() && $iter->is_liked_by(get_identity()->member()),
				'likes' => count($iter->get_likes()),
			]);

		return $this->view->redirect($this->generate_url('poll', [
			'id' => $this->get_poll()->get_id(),
		]));
	}

	protected function run_impl()
	{
		// Verify we have a poll to comment on
		$this->get_poll();
		return parent::run_impl();
	}
}
