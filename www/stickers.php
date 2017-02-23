<?php

require_once 'include/controllers/ControllerCRUD.php';

class ControllerStickers extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelSticker');

		$this->view = View::byName('stickers', $this);
	}

	protected function _create(DataIter $iter, array $data, array &$errors)
	{
		$data['toegevoegd_op'] = date('Y-m-d');
		$data['toegevoegd_door'] = get_identity()->get('id');

		return parent::_create($iter, $data, $errors);
	}

	public function link_to_add_photo(DataIter $iter)
	{
		return $this->link_to('add_photo', $iter);
	}

	public function link_to_photo(DataIter $iter)
	{
		return $this->link_to('photo', $iter);
	}

	public function run_read(DataIter $iter)
	{
		return $this->view->redirect('stickers.php?sticker=' . $iter['id']);
	}

	public function run_photo(DataIter $iter)
	{
		$thumbnail = !empty($_GET['thumbnail']);

		if ($thumbnail)
			return $this->view->render_photo_thumbnail($iter);
		else
			return $this->view->render_photo($iter);
	}

	protected function run_add_photo(DataIter $sticker)
	{
		if ($sticker && get_policy($this->model)->user_can_update($sticker))
		{
			// Set the new photo
			$this->model->setPhoto($sticker, fopen($_FILES['photo']['tmp_name'], 'rb'));

			// Delete the old one from the cache
			$this->view->delete_thumbnail($sticker);
		}

		return $this->view->redirect('stickers.php?sticker=' . $sticker['id']);
	}
	
}

$controller = new ControllerStickers();
$controller->run();
