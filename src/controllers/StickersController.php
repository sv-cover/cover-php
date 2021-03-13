<?php
namespace App\Controller;

require_once 'include/controllers/ControllerCRUD.php';

class StickersController extends \ControllerCRUD
{
	protected $view_name = 'stickers';

	public function __construct($request, $router)
	{
		$this->model = \get_model('DataModelSticker');

		parent::__construct($request, $router);
	}

	protected function _create(\DataIter $iter, array $data, array &$errors)
	{
		$data['toegevoegd_op'] = date('Y-m-d');
		$data['toegevoegd_door'] = get_identity()->get('id');

		return parent::_create($iter, $data, $errors);
	}

	public function new_iter()
	{
		$iter = $this->model->new_iter();

		if (!empty($_GET['lat']))
			$iter['lat'] = $_GET['lat'];

		if (!empty($_GET['lng']))
			$iter['lng'] = $_GET['lng'];

		return $iter;
	}

	public function link_to($view, \DataIter $iter = null, array $arguments = [])
	{
		if (!array_key_exists('next', $arguments) && !empty($_GET['next']) && is_safe_redirect($_GET['next']))
			$arguments['next'] = $_GET['next'];

		return parent::link_to($view, $iter, $arguments);
	}

	public function link_to_next()
	{
		if (!empty($_GET['next']) && is_safe_redirect($_GET['next']))
			return $_GET['next'];
		return $this->link_to('index', null, ['next' => null]);
	}

	public function link_to_add_photo(\DataIter $iter)
	{

		return $this->link_to('add_photo', $iter);
	}

	public function link_to_geojson()
	{
		return $this->link_to('geojson');
	}

	public function link_to_photo(\DataIter $iter)
	{
		return $this->link_to('photo', $iter);
	}

	public function run_read(\DataIter $iter)
	{
		return $this->view->redirect('stickers.php?point=' . $iter['id']);
	}

	public function run_photo(\DataIter $iter)
	{
		$thumbnail = !empty($_GET['thumbnail']);

		if ($thumbnail)
			return $this->view->render_photo_thumbnail($iter);
		else
			return $this->view->render_photo($iter);
	}

	protected function run_add_photo(\DataIter $iter)
	{
		$error = null;

		if ($iter && $this->_form_is_submitted('add_photo', $iter))
		{
			if (!get_policy($this->model)->user_can_update($iter))
				$error = __("You're not allowed to upload a photo for this sticker");

			elseif ($_FILES['photo']['error'] == UPLOAD_ERR_INI_SIZE)
				$error = sprintf(__('The image file is too large. The maximum file size is %s.'),
					ini_get('upload_max_filesize') . ' bytes');

			elseif ($_FILES['photo']['error'] != UPLOAD_ERR_OK)
				$error = sprintf(__('The image hasn’t been uploaded correctly. PHP reports error code %d.'), $_FILES['photo']['error']);

			elseif (!is_uploaded_file($_FILES['photo']['tmp_name']))
				$error = __('The image file is not a file uploaded by PHP.');

			elseif (!($image_meta = @getimagesize($_FILES['photo']['tmp_name'])))
				$error = __("The uploaded file doesn't appear to be an image.");

			else {
				// No errors!

				// Set the new photo
				$this->model->setPhoto($iter, fopen($_FILES['photo']['tmp_name'], 'rb'));

				// Delete the old one from the cache
				$this->view->delete_thumbnail($iter);

				// Ensure we're redirecting to point
				$next_url = edit_url($this->link_to_next(), ['point' => $iter['id']]);
				return $this->view->redirect($next_url);
			}
		}

		return $this->view->render_add_photo($iter, $error);
	}

	protected function run_geojson()
	{
		$features = [];

		$policy = \get_policy($this->model());

		foreach ($this->model->get() as $iter)
		{
			if ($policy->user_can_read($iter))
				$features[] = [
					'type' => 'Feature',
					'geometry' => [
						'type' => 'Point',
						'coordinates' => [
							$iter['lng'],
							$iter['lat']
						]
					],
					'properties' => [
						'id' => $iter['id'],
						'label' => $iter['label'],
						'description' => $iter['omschrijving'],
						'photo_url' => $iter['foto'] ? $this->link_to_photo($iter) : null,
						'added_on' => $iter['toegevoegd_op'],
						'added_by_url' => $iter['toegevoegd_door'] ? sprintf('profiel.php?lid=%d', $iter['toegevoegd_door']) : null,
						'added_by_name' => $iter['toegevoegd_door']
							? member_full_name($iter['member'], BE_PERSONAL)
							: null,
						'editable' => $policy->user_can_update($iter),
						'add_photo_url' => $policy->user_can_update($iter) ? $this->link_to_add_photo($iter) : null,
						'delete_url' => $policy->user_can_delete($iter) ? $this->link_to_delete($iter) : null,
					]
				];
		}

		return $this->view->render_json([
			'type' => 'FeatureCollection',
			'features' => $features,
		]);
	}

}

// $controller = new ControllerStickers();
// $controller->run();
