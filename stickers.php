<?php

require_once 'include/controllers/ControllerCRUD.php';

class ControllerStickers extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelStickers');

		$this->view = View::byName('stickers', $this);
	}

	protected function _create($data, &$errors)
	{
		$data['toegevoegd_op'] = date('Y-m-d');
		$data['toegevoegd_door'] = get_identity()->get('id');

		return parent::_create($data, $errors);
	}

	public function link_to_add_photo(DataIter $iter)
	{
		return $this->link_to_iter($iter, [$this->_var_view => 'add_photo']);
	}

	public function link_to_photo(DataIter $iter)
	{
		return $this->link_to_iter($iter, [$this->_var_view => 'photo']);
	}

	public function run_read(DataIter $iter)
	{
		return $this->redirect('stickers.php?sticker=' . $iter->get_id());
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

		return $this->view->redirect('stickers.php?sticker=' . $sticker->get_id());
	}
	
}

$controller = new ControllerStickers();
$controller->run();
