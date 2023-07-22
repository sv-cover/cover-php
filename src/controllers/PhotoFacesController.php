<?php
namespace App\Controller;

require_once 'src/controllers/PhotoBooksController.php';
require_once 'src/framework/member.php';
require_once 'src/framework/controllers/ControllerCRUD.php';

/**
 * Controller for face tagging in photo albums.
 * Still uses ControllerCRUD (not ControllerCRUDForm), because it relies on the JSON responses, for
 * which this feature seems to be the reason to exist. Trace these commits:
 * d3552107bcffd8aab4c3af426ce7156ae72e3d68 - implementation of json in controllers
 * 38a42d845dddd779d78e10ccc783d0dcb7512a97 - implementation of tagging (committeed a minute after the previous)
 * 8c91b6c52e7549f11b69e8ec6badf41a8368f70a - JSON moved to CRUDView
 */
class PhotoFacesController extends \ControllerCRUD
{
	use PhotoBookRouteHelper;

	protected $_var_view = 'view';

	protected $_var_id = 'face_id';

	public function __construct($request, $router)
	{
		$this->model = get_model('DataModelPhotobookFace');

		parent::__construct($request, $router, false); // make sure parent doesn't initiate a view

		$this->view = new \CRUDView($this);
	}

	public function path(string $view, \DataIter $iter = null, bool $json = false)
	{
		$parameters = [
			$this->_var_view => $view,
			'photo' => $this->get_photo()->get_id(),
		];


		if (isset($iter))
		{
			$parameters[$this->_var_id] = $iter->get_id();

			if ($json)
				$parameters['_nonce'] = nonce_generate(nonce_action_name($view, [$iter]));
		}

		if ($view === 'read' || $view === 'update' || $view === 'delete')
			return $this->generate_url('photos.faces.single', $parameters);

		return $this->generate_url('photos.faces', $parameters);
	}

	protected function _create(\DataIter $iter, array $data, array &$errors)
	{
		$data['foto_id'] = $this->get_photo()->get_id();
		$data['tagged_by'] = get_identity()->get('id');
		$data['tagged_on'] = new \DateTime();

		return parent::_create($iter, $data, $errors);
	}

	protected function _update(\DataIter $iter, array $data, array &$errors)
	{
		// Also update who changed it.
		$data['tagged_by'] = get_identity()->get('id');
		$data['tagged_on'] = new \DateTime();

		// Only a custom label XOR a lid_id can be assigned to a tag
		if (isset($data['custom_label']))
			$data['lid_id'] = null;
		elseif (isset($data['lid_id']))
			$data['custom_label'] = null;

		return parent::_update($iter, $data, $errors);
	}

	protected function _index()
	{
		return $this->model->get_for_photo($this->get_photo());
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

	protected function run_impl()
	{
		if (!$this->get_photo())
			throw new \RuntimeException('You cannot access the photo auxiliary functions without also selecting a photo');
		return parent::run_impl();
	}
}
