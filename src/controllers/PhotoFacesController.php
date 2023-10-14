<?php
namespace App\Controller;

require_once 'src/controllers/PhotoBooksController.php';
require_once 'src/framework/member.php';
require_once 'src/framework/controllers/Controller.php';


class PhotoFacesController extends \Controller
{
	use PhotoBookRouteHelper;

	protected $_var_view = 'view';

	protected $_var_id = 'face_id';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPhotobookFace');

		parent::__construct($request, $router, false); // make sure parent doesn't initiate a view

		$this->view = new \View($this);
	}

	protected function _json_augment_iter(\DataIter $iter)
	{
		$links = [];

		$policy = get_policy($this->model);

		if ($policy->user_can_read($iter))
			$links['read'] = $this->path('read', $iter, true);

		if ($policy->user_can_update($iter))
			$links['update'] = $this->path('update', $iter, true);

		if ($policy->user_can_delete($iter))
			$links['delete'] = $this->path('delete', $iter, true);

		$data = $this->get_data_for_iter($iter);

		return array_merge($data, ['__id' => $iter->get_id(), '__links' => $links]);
	}

	public function path(string $view, \DataIter $iter = null)
	{
		$parameters = [
			$this->_var_view => $view,
			'photo' => $this->get_photo()->get_id(),
		];

		if (isset($iter))
			$parameters[$this->_var_id] = $iter->get_id();

		if ($view === 'read' || $view === 'update' || $view === 'delete')
			return $this->generate_url('photos.faces.single', $parameters);

		return $this->generate_url('photos.faces', $parameters);
	}

	public function get_data_for_iter(\DataIterPhotobookFace $iter)
	{
		if ($iter['lid_id'])
			$suggested_member = null;
		else
			$suggested_member = $iter['suggested_member'];

		if ($suggested_member && !get_policy($suggested_member)->user_can_read($suggested_member))
			$suggested_member = null;

		return [
			'id' => $iter['id'],
			'photo_id' => $iter['foto_id'],
			'x' => $iter['x'],
			'y' => $iter['y'],
			'h' => $iter['h'],
			'w' => $iter['w'],
			'member_id' => $iter['lid_id'],
			'member_full_name' => $iter['lid'] ? member_full_name($iter['lid'], BE_PERSONAL) : null,
			'member_url' => $iter['lid_id'] ? $this->generate_url('profile', ['lid' => $iter['lid_id']]) : null,
			'custom_label' => $iter['custom_label'],
			'suggested_id' => $suggested_member ? $suggested_member['id'] : null,
			'suggested_full_name' => $suggested_member ? member_full_name($suggested_member, BE_PERSONAL) : null,
			'suggested_url' => $suggested_member ? $this->generate_url('profile', ['lid' => $suggested_member['id']]) : null,
		];
	}

	public function run_create()
	{
		$iter = $this->model->new_iter();

		if (!get_policy($this->model)->user_can_create($iter))
			throw new UnauthorizedException('You are not allowed to tag people.');

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = $_POST;
			$data['foto_id'] = $this->get_photo()->get_id();
			$data['tagged_by'] = get_identity()->get('id');
			$data['tagged_on'] = new \DateTime();
			$iter->set_all($data);
			$this->model->insert($iter);
			return $this->view->render_json([
				'iter' => $this->_json_augment_iter($iter)
			]);
		}

		return $this->view->render_json([]);
	}

	public function run_read(\DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_read($iter))
			throw new UnauthorizedException('You are not allowed to see this tag.');

		return $this->view->render_json(['iter' => $this->_json_augment_iter($iter)]);
	}

	public function run_update(\DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_update($iter))
			throw new UnauthorizedException('You are not allowed to edit this tag.');

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$data = $_POST;

			// Also update who changed it.
			$data['tagged_by'] = get_identity()->get('id');
			$data['tagged_on'] = new \DateTime();

			// Only a custom label XOR a lid_id can be assigned to a tag
			if (isset($data['custom_label']))
				$data['lid_id'] = null;
			elseif (isset($data['lid_id']))
				$data['custom_label'] = null;

			foreach ($data as $key => $value)
				$iter->set($key, $value);

			if ($this->model->update($iter) > 0)
				return $this->view->render_json([
					'iter' => $this->_json_augment_iter($iter)
				]);
		}

		return $this->view->render_json([]);
	}

	public function run_delete(\DataIter $iter)
	{
		if (!get_policy($this->model)->user_can_delete($iter))
			throw new UnauthorizedException('You are not allowed to delete this tag.');

		if ($_SERVER['REQUEST_METHOD'] == 'POST')
			$this->model->delete($iter);

		return $this->view->render_json([]);
	}

	public function run_index()
	{
		$iters = array_filter(
			$this->model->get_for_photo($this->get_photo()),
			[get_policy($this->model), 'user_can_read']
		);

		$links = [];
		$new_iter = $this->model()->new_iter();

		if (get_policy($new_iter)->user_can_create($new_iter))
			$links['create'] = $this->path('create', $new_iter, true);

		return $this->view->render_json([
			'iters' => array_map([$this, '_json_augment_iter'], $iters),
			'__links' => $links
		]);
	}

	protected function run_impl()
	{
		if (!$this->get_photo())
			throw new \RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');

		$iter = null;
		$view = $this->get_parameter($this->_var_view);
		$id = $this->get_parameter($this->_var_id);

		if (!empty($id)) {
			$iter = $this->model->get_iter($id);

			if (!$view)
				$view = 'read';

			if (!$iter)
				throw new \NotFoundException('Could not find the tag.');
		}

		if (!$view)
			$view = 'index';

		if (!in_array($view, ['create', 'read', 'update', 'delete', 'index']))
			throw new \NotFoundException('View ' . $view . ' not found.');

		if ($view == 'index')
			return $this->run_index();

		if ($view == 'create')
			return $this->run_create();

		if ($iter === null)
			throw new \NotFoundException('View ' .$view . ' requires an iterator, but none was specified');

		return call_user_func([$this, 'run_' . $view], $iter);
	}
}
