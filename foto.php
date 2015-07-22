<?php
	require_once 'include/init.php';
	require_once 'include/controllers/Controller.php';
	
	class Controllerfoto extends Controller
	{
		const FORMAT_PORTRAIT = 'portrait';
		const FORMAT_SQUARE = 'square';

		public function __construct()
		{
			$this->model = get_model('DataModelMember');
		}

		protected function _generate_thumbnail($photo, $format, $width)
		{
			$imagick = new Imagick();
			$imagick->readImageBlob($photo);
			
			if ($format == self::FORMAT_SQUARE)
			{
				$y = 0.05 * $imagick->getImageHeight(); // TODO Find the face :O
				$size = min($imagick->getImageWidth(), $imagick->getImageHeight());

				if ($y + $size > $imagick->getImageHeight())
					$y = 0;

				$imagick->cropImage($size, $size, 0, $y);
			}

			$imagick->scaleImage($width, 0);
			
			// Send proper headers: cache control & mime type
			header('Pragma: public');
			header('Cache-Control: max-age=86400');
			header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
			header('Content-Type: image/jpeg');

			// Write image to php output buffer
			$out = fopen('php://output', 'w');
			$imagick->setImageFormat('jpeg');
			$imagick->writeImageFile($out);

			// And clean up.
			fclose($out);
			$imagick->destroy();
		}

		protected function _generate_placeholder(DataIterMember $member, $format, $width)
		{
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
				0, // angle
				$text);

			// Send proper headers: cache control & mime type
			header('Pragma: public');
			header('Cache-Control: max-age=86400');
			header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
			header('Content-Type: image/png');

			// Write image to php output buffer
			$out = fopen('php://output', 'w');
			$imagick->setImageFormat('png');
			$imagick->writeImageFile($out);

			// And clean up.
			fclose($out);
			$imagick->destroy();
		}

		protected function _view_thumbnail(DataIterMember $member, $format)
		{
			$format = in_array($format, [self::FORMAT_SQUARE, self::FORMAT_PORTRAIT])
				? $format
				: self::FORMAT_PORTRAIT;

			$width = isset($_GET['width'])
				? min($_GET['width'], 600)
				: 600;

			if ($this->model->is_private($member, 'foto') || !$this->model->has_picture($member))
				return $this->_generate_placeholder($member, $format, $width);
			else
				return $this->_generate_thumbnail($this->model->get_photo($member), $format, $width);
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
