<?php
	namespace App\Controller;

	require_once 'src/framework/controllers/Controller.php';
	
	class ProfilePictureController extends \Controller
	{
		const FORMAT_PORTRAIT = 'portrait';
		const FORMAT_SQUARE = 'square';

		const TYPE_ORIGINAL = 'original';
		const TYPE_THUMBNAIL = 'thumbnail';
		const TYPE_PLACEHOLDER_PRIVATE = 'placeholder-private';
		const TYPE_PLACEHOLDER_PUBLIC = 'placeholder-public';

		const MAX_WIDTH = 2000;

		public function __construct($request, $router)
		{
			$this->model = get_model('DataModelMember');

			parent::__construct($request, $router);
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

		protected function _format_cache_file_path(\DataIterMember $member, $width, $height, $type)
		{
			$file_path_format = get_config_value('path_to_scaled_profile_picture', null);

			if ($file_path_format === null)
				return null;

			$extension = $this->_is_placeholder($type) ? 'png' : 'jpg';

			return sprintf($file_path_format, $member->get_id(), $width, $height, $type, $extension);
		}

		protected function _open_cache_stream(\DataIterMember $member, $width, $height, $type, $mode)
		{
			$file_path = $this->_format_cache_file_path($member, $width, $height, $type);

			if ($file_path === null)
				return null;

			if (!file_exists($file_path))
			{
				// If we were trying to read, stop trying, it won't work, the file does not exist
				if ($mode[0] == 'r')
					return null;

				// However, if we were trying to write, make sure the directory exists and make it otherwise.
				if ($mode[0] == 'w' && !file_exists(dirname($file_path)))
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

			// Content-Length seems to break HTTP/2
			// if ($length !== null)
			// 	header(sprintf('Content-Length: %d', $length));

			if ($type !== null)
				header(sprintf('Content-Type: %s', $type));
			
			fpassthru($fout);
		}

		protected function _view_cached(\DataIterMember $member, $width, $height, $type)
		{
			$file_path = $this->_format_cache_file_path($member, $width, $height, $type);

			// If we can't open it, we can't serve it.
			if (!($fh = $this->_open_cache_stream($member, $width, $height, $type, 'rb')))
				return false;

			// If it is outdated, close it again and tell our caller that we can't serve it.
			if (!$this->_is_placeholder($type) && $this->model->get_photo_mtime($member) > filemtime($file_path))
			{
				fclose($fh);
				return false;
			}

			$last_modified = gmdate(DATE_RFC1123,filemtime($file_path));

			// Don't send a file if the browser has a cached one already
			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $last_modified) {
				header('Pragma: public');
				header('Cache-Control: max-age=86400');
				http_response_code(304);
				fclose($fh);
				return true;
			}

			header('Last-Modified: ' . $last_modified);

			// Serve the actual stream including the appropriate headers
			$this->_serve_stream($fh,
				$this->_is_placeholder($type) ? 'image/png' : 'image/jpeg',
				filesize($file_path));
			fclose($fh);

			// Let them know we succeeded, no need to generate a new image.
			return true;
		}

		protected function _generate_original(\DataIterMember $member)
		{
			$photo = $this->model->get_photo_stream($member);
			
			if (!$photo)
				throw new \NotFoundException('Member has no photo');

			$imagick = new \Imagick();
			$imagick->readImageFile($photo['foto']);

			apply_image_orientation($imagick);

			strip_exif_data($imagick);

			// Oh shit cache not writable? Fall back to a temp stream.
			$fout = $this->_open_cache_stream($member, 0, 0, self::TYPE_ORIGINAL, 'w+') or $fout = fopen('php://temp', 'w+');

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

		protected function _generate_thumbnail(\DataIterMember $member, $format, $width)
		{
			$photo = $this->model->get_photo_stream($member);
			
			if (!$photo)
				throw new \NotFoundException('Member has no photo');

			$imagick = new \Imagick();
			$imagick->readImageFile($photo['foto']);

			apply_image_orientation($imagick);

			strip_exif_data($imagick);

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

		protected function _generate_placeholder(\DataIterMember $member, $format, $width)
		{
			if ($member->is_private('naam'))
				$text = '?';
			else
				$text = mb_strtoupper(sprintf('%s%s',
					mb_substr(trim($member->get('voornaam')), 0, 1),
					mb_substr(trim($member->get('achternaam')), 0, 1)));

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

			$imagick = new \Imagick();
			$draw = new \ImagickDraw();

			$hash = md5($member->get('voornaam') . $member->get('achternaam'));
			$random_r = hexdec(substr($hash, 0, 2));
			$random_g = hexdec(substr($hash, 2, 2));
			$random_b = hexdec(substr($hash, 4, 2));

			$s_r = 0.213 * $random_r;
			$s_g = 0.715 * $random_g;

			$random_b = max($random_b, (0.5 - ($s_r + $s_g)) / 0.072);

			$s_b = 0.072 * $random_b;
			assert($s_r + $s_g + $s_b >= 0.5);

			$background = new \ImagickPixel(sprintf('#%02x%02x%02x', $random_r, $random_g, $random_b));
			$foreground = '#fff';

			$imagick->newImage($width, $height, $background);

			$draw->setFillColor($foreground);
			$draw->setFont(realpath ('public/fonts/FiraSans-Regular.ttf'));
			$draw->setFontSize($width / 2);
			$draw->setTextAntialias(true);

			$metrics = $imagick->queryFontMetrics($draw, $text);

			$imagick->annotateImage($draw,
				($width - $metrics['textWidth']) / 2, // x
				($width - $metrics['boundingBox']['y2']) / 2 + $metrics['boundingBox']['y2'], // y
				0, // angl
				$text);

			// Height is 0 in cache name for portrait 
			$cache_height = $format === self::FORMAT_PORTRAIT ? 0 : $height;
			// Oh shit cache not writable? Fall back to a temp stream.
			$fout = $this->_open_cache_stream($member, $width, $cache_height, $this->_get_placeholder_type($member), 'w+') or $fout = fopen('php://temp', 'w+');

			$imagick->setImageFormat('png');
			$imagick->writeImageFile($fout);
			$imagick->destroy();

			fseek($fout, 0, SEEK_END);
			$file_size = ftell($fout);
			rewind($fout);

			// $this->_serve_stream($fout, 'image/png', $file_size);

			// And clean up.
			fclose($fout);

			return true;
		}

		protected function _view_thumbnail(\DataIterMember $member, $format)
		{
			$format = in_array($format, [self::FORMAT_SQUARE, self::FORMAT_PORTRAIT])
				? $format
				: self::FORMAT_PORTRAIT;

			$width = min(intval($this->get_parameter('width') ?? self::MAX_WIDTH), self::MAX_WIDTH);

			$height = $format == self::FORMAT_SQUARE ? $width : 0;

			if ($this->model->is_private($member, 'foto') || !$this->model->has_picture($member))
				return $this->_view_cached($member, $width, $height, $this->_get_placeholder_type($member))
					or $this->_generate_placeholder($member, $format, $width);
			else
				return $this->_view_cached($member, $width, $height, self::TYPE_THUMBNAIL)
					or $this->_generate_thumbnail($member, $format, $width);
		}

		protected function _view_photo(\DataIterMember $member)
		{
			if ($this->model->is_private($member, 'foto'))
				throw new \UnauthorizedException('Photo is private');

			if (!$this->model->has_picture($member))
				return new \NotFoundException('Member has no photo');

			return $this->_view_cached($member, 0, 0, self::TYPE_ORIGINAL)
				or $this->_generate_original($member);
		}
		
		protected function run_impl()
		{
			$member_id = $this->get_parameter('lid_id');
			if (empty($member_id))
				return new \Exception('No member ID provided');

			$iter = $this->model->get_iter($member_id);

			$format = $this->get_parameter('format');

			if (isset($format))
				return $this->_view_thumbnail($iter, $format);
			else 
				return $this->_view_photo($iter);
		}
	}
