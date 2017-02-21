<?php
	require_once 'include/init.php';
	require_once 'include/controllers/Controller.php';
	
	class Controllerfoto extends Controller
	{
		const FORMAT_PORTRAIT = 'portrait';
		const FORMAT_SQUARE = 'square';

		const TYPE_THUMBNAIL = 'thumbnail';
		const TYPE_PLACEHOLDER_PRIVATE = 'placeholder-private';
		const TYPE_PLACEHOLDER_PUBLIC = 'placeholder-public';

		public function __construct()
		{
			$this->model = get_model('DataModelMember');

			$this->view = new View($this);
		}

		protected function _get_placeholder_type($member)
		{
			if ($member->is_private('naam'))
				return self::TYPE_PLACEHOLDER_PUBLIC;

			return self::TYPE_PLACEHOLDER_PRIVATE;
		}

		protected function _is_placeholder($type)
		{
			return $type == self::TYPE_PLACEHOLDER_PUBLIC || $type == self::TYPE_PLACEHOLDER_PRIVATE;
		}

		protected function _format_cache_file_path(DataIterMember $member, $width, $height, $type)
		{
			$file_path_format = get_config_value('path_to_scaled_profile_picture', null);

			if ($file_path_format === null)
				return null;

			$extension = $this->_is_placeholder($type) ? 'png' : 'jpg';

			return sprintf($file_path_format, $member->get_id(), $width, $height, $type, $extension);
		}

		protected function _open_cache_stream(DataIterMember $member, $width, $height, $type, $mode)
		{
			$file_path = $this->_format_cache_file_path($member, $width, $height, $type);

			if ($file_path === null)
				return null;

			if (!file_exists($file_path))
			{
				// If we were trying to read, stop trying, it won't work, the file does not exist
				if ($mode{0} == 'r')
					return null;

				// However, if we were trying to write, make sure the directory exists and make it otherwise.
				if ($mode{0} == 'w' && !file_exists(dirname($file_path)))
					mkdir(dirname($file_path), 0777, true);
			}

			return fopen($file_path, $mode);
		}

		protected function _serve_stream($fout, $type = null, $length = null)
		{
			// Send proper headers: cache control & mime type
			header('Pragma: public');
			header('Cache-Control: max-age=86400');
			header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

			if ($length > 0)
				header(sprintf('Content-Length: %d', $length));

			if ($type !== null)
				header(sprintf('Content-Type: %s', $type));
			
			fpassthru($fout);
		}

		protected function _view_cached(DataIterMember $member, $width, $height, $type)
		{
			$file_path = $this->_format_cache_file_path($member, $width, $height, $type);

			// If we can't open it, we can't serve it.
			if (!($fh = $this->_open_cache_stream($member, $width, $height, $type, 'rb')))
				return false;

			// If it is outdated, close it again and tell our caller that we can't serve it.
			if ($this->model->get_photo_mtime($member) > filemtime($file_path))
			{
				fclose($fh);
				return false;
			}

			// Send an extra header with the mtime to make debugging the cache easier
			header('X-Cache: ' . date('r', filemtime($file_path)));

			// Serve the actual stream including the appropriate headers
			$this->_serve_stream($fh,
				$this->_is_placeholder($type) ? 'image/png' : 'image/jpeg',
				filesize($file_path));
			fclose($fh);

			// Let them know we succeeded, no need to generate a new image.
			return true;
		}

		protected function _generate_thumbnail(DataIterMember $member, $format, $width)
		{
			$imagick = new Imagick();
			$imagick->readImageBlob($this->model->get_photo($member));
			$height = 0;
			
			if ($format == self::FORMAT_SQUARE)
			{
				$y = 0.05 * $imagick->getImageHeight(); // TODO Find the face :O
				$size = min($imagick->getImageWidth(), $imagick->getImageHeight());
				$height = $width; // because square

				if ($y + $size > $imagick->getImageHeight())
					$y = 0;

				$imagick->cropImage($size, $size, 0, $y);
			}

			$imagick->scaleImage($width, 0);

			// Oh shit cache not writable? Fall back to a temp stream.
			$fout = $this->_open_cache_stream($member, $width, $height, self::TYPE_THUMBNAIL, 'w+') or $fout = fopen('php://temp', 'w+');

			// Write image to php output buffer
			$imagick->setImageFormat('jpeg');
			$imagick->writeImageFile($fout);
			$imagick->destroy();

			fseek($fout, 0, SEEK_END);
			$file_size = ftell($fout);
			rewind($fout);

			$this->_serve_stream($fout, 'image/jpeg', $file_size);

			// And clean up.
			fclose($fout);

			return true;
		}

		protected function _generate_placeholder(DataIterMember $member, $format, $width)
		{
			if ($member->is_private('naam'))
				$text = '?';
			else
				$text = sprintf('%s%s',
					substr(trim($member->get('voornaam')), 0, 1),
					substr(trim($member->get('achternaam')), 0, 1));

			switch ($format)
			{
				case self::FORMAT_SQUARE:
					$height = $width;
					break;

				case self::FORMAT_PORTRAIT:
				default:
					$height = 1.5 * $width;
					break;
			}

			$imagick = new Imagick();
			$draw = new ImagickDraw();

			$hash = md5($member->get('voornaam') . $member->get('achternaam'));
			$random_r = hexdec(substr($hash, 0, 2));
			$random_g = hexdec(substr($hash, 2, 2));
			$random_b = hexdec(substr($hash, 4, 2));

			$s_r = 0.213 * $random_r;
			$s_g = 0.715 * $random_g;

			$random_b = max($random_b, (0.5 - ($s_r + $s_g)) / 0.072);

			$s_b = 0.072 * $random_b;
			assert('$s_r + $s_g + $s_b >= 0.5');

			$background = new ImagickPixel(sprintf('#%02x%02x%02x', $random_r, $random_g, $random_b));
			$foreground = '#fff';

			$imagick->newImage($width, $height, $background);

			$draw->setFillColor($foreground);
			$draw->setFont('fonts/verdana.ttf');
			$draw->setFontSize($width / 2);
			$draw->setTextAntialias(true);

			$metrics = $imagick->queryFontMetrics($draw, $text);

			$imagick->annotateImage($draw,
				($width - $metrics['textWidth']) / 2, // x
				($width - $metrics['boundingBox']['y2']) / 2 + $metrics['boundingBox']['y2'], // y
				0, // angl
				$text);

			// Oh shit cache not writable? Fall back to a temp stream.
			$fout = $this->_open_cache_stream($member, $width, $height, $this->_get_placeholder_type($member), 'w+') or $fout = fopen('php://temp', 'w+');

			$imagick->setImageFormat('png');
			$imagick->writeImageFile($fout);
			$imagick->destroy();

			fseek($fout, 0, SEEK_END);
			$file_size = ftell($fout);
			rewind($fout);

			$this->_serve_stream($fout, 'image/png', $file_size);

			// And clean up.
			fclose($fout);

			return true;
		}

		protected function _view_thumbnail(DataIterMember $member, $format)
		{
			$format = in_array($format, [self::FORMAT_SQUARE, self::FORMAT_PORTRAIT])
				? $format
				: self::FORMAT_PORTRAIT;

			$width = isset($_GET['width'])
				? min($_GET['width'], 600)
				: 600;

			$height = $format == self::FORMAT_SQUARE ? $width : 0;

			if ($this->model->is_private($member, 'foto') || !$this->model->has_picture($member))
				return $this->_view_cached($member, $width, $height, $this->_get_placeholder_type($member))
					or $this->_generate_placeholder($member, $format, $width);
			else
				return $this->_view_cached($member, $width, $height, self::TYPE_THUMBNAIL)
					or $this->_generate_thumbnail($member, $format, $width);
		}

		protected function _view_photo(DataIterMember $member)
		{
			if ($this->model->is_private($member, 'foto'))
				throw new UnauthorizedException('Photo is private');

			if (!$this->model->has_picture($member))
				return new NotFoundException('Member has no photo');

			$photo = $this->model->get_photo($member);

			header('Pragma: public');
			header('Cache-Control: max-age=86400');
			header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
			header('Content-Type: image/jpeg');
			header('Content-Length: ' . strlen($photo));

			echo $photo;
		}
		
		protected function run_impl()
		{
			$iter = $this->model->get_iter($_GET['lid_id']);

			if (isset($_GET['format']))
				return $this->_view_thumbnail($iter, $_GET['format']);
			else 
				return $this->_view_photo($iter);
		}
	}
	
	$controller = new Controllerfoto();
	$controller->run();
