<?php

class Rectangle
{
	public $x, $y, $width, $height;

	public function __construct($width, $height, $x = 0, $y = 0)
	{
		$this->width = $width;
		$this->height = $height;
		$this->x = $x;
		$this->y = $y;
	}

	public function __toString()
	{
		return sprintf('Rectangle(width:%d, height:%d, x:%d, y:%d)',
			$this->width, $this->height, $this->x, $this->y);
	}
}

abstract class ControllerImage extends Controller
{
	const FORMAT_JPEG = 1;
	const FORMAT_PNG = 2;

	protected $format = self::FORMAT_JPEG;

	abstract protected function getImage(DataIter $iter);

	abstract protected function getLastModified(DataIter $iter);

	protected function getFilename(DataIter $iter)
	{
		switch ($this->format)
		{
			case self::FORMAT_JPEG:
				$extension = 'jpg';
				break;

			case self::FORMAT_PNG:
				$extension = 'png';
				break;

			default:
				$extension = 'bin';
				break;
		}

		return sprintf('%s/%d.%s',
			strtolower(get_class($this)),
			$iter->get_id(),
			$extension);
	}

	protected function getDimensions(DataIter $iter, Rectangle $original)
	{
		return $original;
	}

	protected function clientHasLatestVersion($last_modified)
	{
		if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
			return false;

		if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) < $last_modified)
			return false;

		header('HTTP/1.0 304 Not Modified');
		return true;
	}

	protected function generateImage(DataIter $iter, $target_file)
	{
		$original = $this->getImageResource($iter);

		$src_dim = $this->getResourceDimensions($original);

		$dst_dim = $this->getDimensions($iter, $src_dim);

		if ($src_dim != $dst_dim)
			$res = $this->getScaledResource($original, $src_dim, $dst_dim);
		else
			$res = $original;

		$this->saveResource($res, $target_file);
	}

	protected function getImageResource(DataIter $iter)
	{
		$image = $this->getImage($iter);

		return imagecreatefromstring($image);
	}

	protected function getScaledResource($original, $src_dim, $dst_dim)
	{
		$res = imagecreatetruecolor($dst_dim->width, $dst_dim->height);

		imagecopyresampled($res, $original,
			$dst_dim->x, $dst_dim->y,
			$src->x, $src->y,
			$dst_dim->width, $dst_dim->height,
			$src_dim->width, $src_dim->height);

		return $res;
	}

	protected function getResourceDimensions($res)
	{
		return new Rectangle(imagesx($res), imagesy($res));
	}

	protected function saveResource($res, $filename)
	{
		switch ($this->format)
		{
			case self::FORMAT_JPEG:
				imagejpeg($res, $filename);
				break;

			case self::FORMAT_PNG:
				imagepng($res, $filename);
				break;
		}
	}

	protected function sendNotFoundResponse()
	{
		header('HTTP/1.0 404 Not Found');
		echo 'File not found';
		exit;
	}

	protected function sendHeaders(DataIter $iter)
	{
		header('Pragma: public');
		header('Cache-Control: max-age=86400');
		header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

		$this->sendContentTypeHeader();
	}

	protected function sendContentTypeHeader()
	{
		switch ($this->format)
		{
			case self::FORMAT_JPEG:
				header('Content-Type: image/jpeg');
				break;

			case self::FORMAT_PNG:
				header('Content-Type: image/png');
				break;

			default:
				header('Content-Type: application/octet-stream');
				break;
		}
	}

	protected function getIter()
	{
		return $this->model->get_iter($_GET['id']);
	}

	public function run_image(DataIter $iter)
	{
		// Skip cache if the photo is hidden or anything
		$skip_cache = !empty($_GET['skip_cache']);

		$last_modified = $this->getLastModified($iter);

		if (!$last_modified)
			return $this->sendNotFoundResponse();

		if (!$skip_cache && $this->clientHasLatestVersion($last_modified))
			return;

		// If we skip the cache, write the thumnail directly to the output
		$cache_file = $skip_cache ? null : 'tmp/' . $this->getFilename($iter);

		$serve_from_cache = !$skip_cache
			&& file_exists($cache_file)
			&& $last_modified < filemtime($cache_file);

		$this->sendHeaders($iter);
		header('X-Source: ' . ($serve_from_cache ? 'cache' : 'database'));

		if (!$serve_from_cache)
		{
			if ($cache_file && !file_exists(dirname($cache_file)))
				mkdir(dirname($cache_file), 0777, true);

			$this->generateImage($iter, $cache_file);
		}

		// Only if the cache was used, fetch the image from the cache file
		if ($cache_file)
			readfile($cache_file);
	}

	public function run_impl()
	{
		$this->run_image($this->getIter());
	}
}