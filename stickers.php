<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'controllers/Controller.php';

class ControllerStickers extends Controller
{
	public function ControllerStickers()
	{
		$this->model = get_model('DataModelStickers');
	}

	public function get_content($view, $iter = null, $params = null)
	{
		$this->run_header(array('title' => __('Stickers')));
		run_view('stickers::' . $view, $this->model, $iter, $params);
		$this->run_footer();
	}

	public function run()
	{
		if (isset($_POST['action']) && $_POST['action'] == 'add_sticker')
			$this->_add_sticker($_POST);

		elseif (isset($_POST['action']) && $_POST['action'] == 'add_photo')
			$this->_add_photo($_POST);

		else if (isset($_POST['action']) && $_POST['action'] == 'remove_sticker')
			$this->_remove_sticker($_POST);

		else if (isset($_GET['photo']))
			$this->run_photo($_GET['photo']);

		$this->run_map();
	}

	public function run_map()
	{
		$stickers = $this->model->get();

		$this->get_content('map', $stickers);
	}

	public function run_photo($id)
	{
		$iter = $this->model->get_iter($id);

		if (!$iter)
		{
			header('Status: 404 Not Found');
			echo 'Sticker not found';
			exit;
		}

		header('Pragma: public');
		header('Cache-Control: max-age=86400');
		header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
		header('Content-Type: image/jpeg');

		echo $this->model->getPhoto($iter);
		exit;
	}

	protected function _add_sticker(array $data)
	{
		if (!logged_in())
			return;

		if (empty($data['label']) || empty($data['lat']) || empty($data['lng']))
			return;

		$id = $this->model->addSticker($data['label'], $data['omschrijving'], $data['lat'], $data['lng']);

		header('Location: stickers.php?sticker=' . $id);
		exit;
	}

	protected function _remove_sticker(array $data)
	{
		$iter = $this->model->get_iter($data['id']);

		if ($iter && $this->model->memberCanEditSticker($iter))
			$this->model->delete($iter);

		header('Location: stickers.php');
		exit;
	}

	protected function _add_photo(array $data)
	{
		$iter = $this->model->get_iter($data['id']);

		if ($iter && $this->model->memberCanEditSticker($iter))
			$this->model->setPhoto($iter, fopen($_FILES['photo']['tmp_name'], 'rb'));

		header('Location: stickers.php');
		exit;
	}
}

$controller = new ControllerStickers;
$controller->run();
