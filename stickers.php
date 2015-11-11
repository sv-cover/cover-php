<?php

require_once 'include/controllers/ControllerCRUD.php';

class ControllerStickers extends ControllerCRUD
{
	public function __construct()
	{
		$this->model = get_model('DataModelStickers');
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

	protected function run_add_photo(DataIter $sticker)
	{
		if ($sticker && get_policy($this->model)->user_can_update($sticker))
			$this->model->setPhoto($sticker, fopen($_FILES['photo']['tmp_name'], 'rb'));

		header('Location: stickers.php?sticker=' . $sticker->get_id());
		exit;
	}

	protected function _generate_thumbnail($sticker)
	{
		$cache_file = 'tmp/stickers/' . $sticker->get_id() . '.jpg';

		$use_cache = file_exists($cache_file) && filemtime($cache_file) > $sticker['foto_mtime'];

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
		$cache_file = 'tmp/stickers/' . $sticker->get_id() . '.jpg';

		if (file_exists($cache_file))
			unlink($cache_file);
	}
}

$controller = new ControllerStickers;
$controller->run();
