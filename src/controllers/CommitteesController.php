<?php
namespace App\Controller;

require_once 'src/framework/controllers/ControllerCRUD.php';

class CommitteesController extends \ControllerCRUD
{	
	protected $_var_id = 'commissie';

	protected $view_name = 'committees';

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

	protected function _create(\DataIter $iter, array $data, array &$errors)
	{
		// Prevent DataIterCommissie::set_members from being called too early
		$iter_data = $data;
		unset($iter_data['members']);

		if (!parent::_create($iter, $iter_data, $errors))
			return false;

		if (!empty($data['members']))
			$this->model->set_members($iter, $data['members']);

		return $iter;
	}

	protected function _update(\DataIter $iter, array $data, array &$errors)
	{
		$data['hidden'] = (array_key_exists('hidden', $data) && $data['hidden'] === 'yes');

		if (!parent::_update($iter, $data, $errors))
			return false;

		$this->model->set_members($iter, empty($data['members']) ? [] : $data['members']);

		return true;
	}

	protected function _delete(\DataIter $iter, array &$errors)
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

	public function run_show_interest(\DataIter $iter)
	{
		if (!get_identity()->is_member())
			throw new \UnauthorizedException('Only active members can apply for a committee');

		if (!get_policy($this->model)->user_can_read($iter))
			throw new \UnauthorizedException('You are not allowed to read this ' . get_class($iter) . '.');

		if ($this->_form_is_submitted('show_interest', $iter)) {
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
		}
		else {
			// Pick a random commissie
			$committee = $this->model->get_random(\DataModelCommissie::TYPE_COMMITTEE);
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
