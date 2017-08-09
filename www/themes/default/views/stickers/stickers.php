<?php
	
class StickersView extends CRUDView
{
	protected $__file = __FILE__;

	protected $model;

	public function scripts()
	{
		return array_merge(parent::scripts(), [
			get_theme_data('data/stickers.js')
		]);
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

	private function _cache_path(DataIter $sticker)
	{
		return path_concat(get_config_value('sticker_cache', 'tmp/stickers'), $sticker->get_id() . '.jpg');
	}

	public function generate_thumbnail(DataIter $sticker)
	{
		$cache_file = $this->_cache_path($sticker);

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
		$cache_file = $this->_cache_path($sticker);

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

	public function encodeStickers($iters)
	{
		$stickers = array();

		$policy = get_policy($this->controller->model());

		foreach ($iters as $iter)
		{
			$sticker = array(
				'id' => $iter['id'],
				'label' => $iter['label'],
				'omschrijving' => $iter['omschrijving'],
				'lat' => $iter['lat'],
				'lng' => $iter['lng'],
				'foto' => $iter['foto'] ? $this->controller->link_to_photo($iter) : null,
				'toegevoegd_op' => $iter['toegevoegd_op'],
				'toegevoegd_door_id' => $iter['toegevoegd_door'],
				'toegevoegd_door_naam' => $iter['toegevoegd_door']
					? member_full_name($iter['member'], BE_PERSONAL)
					: null,
				'editable' => $policy->user_can_update($iter),
				'delete_nonce' => nonce_generate(nonce_action_name('delete', [$iter]))
			);

			$stickers[] = $sticker;
		}

		return json_encode($stickers);
	}
}
