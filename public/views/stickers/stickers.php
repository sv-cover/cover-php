<?php

class StickersView extends CRUDFormView
{
	protected $__file = __FILE__;

	protected $model;

	public function scripts()
	{
		return array_merge(parent::scripts(), [
			get_theme_data('assets/dist/js/maps.js'),
		]);
	}

	public function stylesheets()
	{
		return array_merge(parent::stylesheets(), [
			'https://api.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.css'
		]);
	}

	public function render_delete(DataIter $iter, $form, $success)
	{
		if ($success)
			return $this->redirect($this->controller->get_referrer() ?? $this->controller->generate_url('stickers'));

		return parent::render_delete($iter, $form, $success);
	}

	public function render_photo(DataIter $iter)
	{
		header('Pragma: public');
		header('Cache-Control: max-age=86400');
		header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
		header('Content-Type: image/jpeg');

		return $this->controller->model()->getPhoto($iter);
	}

	public function render_photo_thumbnail(DataIter $iter)
	{
		header('Pragma: public');
		header('Cache-Control: max-age=86400');
		header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
		header('Content-Type: image/jpeg');

		$thumb_file = $this->generate_thumbnail($iter);
		readfile($thumb_file);
	}

	public function generate_thumbnail(DataIter $sticker)
	{
		$cache_file = 'tmp/stickers/' . $sticker->get_id() . '.jpg';

		$use_cache = file_exists($cache_file) && filemtime($cache_file) > $sticker['foto_mtime'];

		// Is the cache file up to date? Then we are done
		if (!$use_cache)
		{		
			$large = imagecreatefromstring($this->controller->model()->getPhoto($sticker));
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

	public function delete_thumbnail(DataIter $sticker)
	{
		$cache_file = 'tmp/stickers/' . $sticker->get_id() . '.jpg';

		if (file_exists($cache_file))
			unlink($cache_file);
	}

	public function location()
	{
		if (isset($_GET['sticker']))
		{
			$sticker = $this->controller->model()->get_iter($_GET['sticker']);
			return sprintf('%f, %f', $sticker->get('lat'), $sticker->get('lng'));
		}
		else
			return '53.20, 6.56'; // Groningen
	}
}
