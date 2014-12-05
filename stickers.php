<?php

require_once 'include/init.php';
require_once 'include/member.php';
require_once 'include/controllers/Controller.php';

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
			$this->run_photo($_GET['photo'], !empty($_GET['thumbnail']));

		$this->run_map();
	}

	public function run_map()
	{
		$stickers = $this->model->get();

		$this->get_content('map', $stickers);
	}

	public function run_photo($id, $thumbnail = false)
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

		if ($thumbnail)
		{
			$thumb_file = $this->_generate_thumbnail($iter);
			readfile($thumb_file);
		}
		else
		{
			echo $this->model->getPhoto($iter);
		}

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
		{
			$this->model->delete($iter);
			$this->_delete_thumbnail($iter);
		}

		header('Location: stickers.php');
		exit;
	}

	protected function _add_photo(array $data)
	{
		$iter = $this->model->get_iter($data['id']);

		if ($iter && $this->model->memberCanEditSticker($iter))
			$this->model->setPhoto($iter, fopen($_FILES['photo']['tmp_name'], 'rb'));

		header('Location: stickers.php?sticker=' . $iter->get('id'));
		exit;
	}

	protected function _generate_thumbnail($sticker)
	{
		$cache_file = 'tmp/stickers/' . $sticker->get('id') . '.jpg';

		$use_cache = file_exists($cache_file) && filemtime($cache_file) > $sticker->get('foto_mtime');

		// Is the cache file up to date? Then we are done
		if (!$use_cache)
		{		
			$large = imagecreatefromstring($this->model->getPhoto($sticker));
			$width = 600;
			$height = $width * imagesy($large) / imagesx($large);
			$thumb = imagecreatetruecolor($width, $height);
			imagecopyresampled($thumb, $large, 0, 0, 0, 0, $width, $height, imagesx($large), imagesy($large));

			if (!file_exists(dirname($cache_file)))
				mkdir(dirname($cache_file), 0777, true);

			imagejpeg($thumb, $cache_file);
		}

		header('X-Source: ' . ($use_cache ? 'cache' : 'database'));

		return $cache_file;
	}

	protected function _delete_thumbnail($sticker)
	{
		$cache_file = 'tmp/stickers/' . $sticker->get('id') . '.jpg';

		if (file_exists($cache_file))
			unlink($cache_file);
	}
}

$controller = new ControllerStickers;
$controller->run();
