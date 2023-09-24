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
			$this->poll = \get_model('DataModelNewPoll')->get_iter($this->get_parameter('poll_id'));
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

	protected function run_impl()
	{
		// Verify we have a poll to comment on
		$this->get_poll();
		return parent::run_impl();
	}
}
