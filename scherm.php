<?php

require_once 'include/init.php';
require_once 'include/markup.php';

class ControllerScherm
{
	// private $search_paths = array(
	// 	'./scherm/slides' => array('allow_php' => true),
	// 	'./test/slides-bestuur' => array('allow_php' => false),
	// 	'./test/slides-promotie' => array('allow_php' => false)
	// );
	private $search_paths;

	private $default_slide;

	public function ControllerScherm(array $config)
	{
		$this->search_paths = $config['search_paths'];

		$this->default_slide = dirname(__FILE__) . '/scherm/default-slide.php';

		$this->slides = $this->search_slides(array_keys($this->search_paths));
	}

	protected function search_slides($search_paths)
	{
		$slides = array();

		foreach ($search_paths as $path)
		{
			foreach (scandir($path) as $folder)
			{
				// Skip dot files
				if ($folder{0} == '.')
					continue;

				// Skip anything not a folder
				if (!is_dir($path . '/' . $folder))
					continue;

				// If it contains a custom slide, add it to the list
				if (file_exists($path . '/' . $folder . '/slide.php'))
					$slides[sha1($path . '/' . $folder)] = $path . '/' . $folder;

				// Or if it is just a folder with files, include it as well
				else if (glob($path . '/' . $folder . '/*.{jpg,png,svg}', GLOB_BRACE))
					$slides[sha1($path . '/' . $folder)] = $path . '/' . $folder;
			}
		}

		return $slides;
	}

	protected function run_slide()
	{
		chdir($this->slides[$this->slide]);

		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");

		if (file_exists('slide.php'))
			include 'slide.php';
		else
			include $this->default_slide;
	}

	protected function run_resource($resource)
	{
		$path = $this->slides[$this->slide] . '/' . $resource;

		if (!file_exists($path))
		{
			header('Status: 404 Not Found');
			echo 'Resource not found';
			return;
		}

		// Find the right mime type for the file
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		
		switch ($extension)
		{
			case 'jpg':
			case 'jpeg':
				$mime_type = 'image/jpeg';
				break;

			case 'gif':
				$mime_type = 'image/gif';
				break;

			case 'png':
				$mime_type = 'image/png';
				break;

			default:
				$mime_type = 'application/octet-stream';
				break;
		}

		// Send the mime type
		header('Content-Type: ' . $mime_type);
		
		// Send some non-caching headers
		// header('Pragma: public');
		// header('Cache-Control: max-age=86400');
		// header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', filemtime($path)));
		// header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
		
		// .. and finally, send the file.
		readfile($path);
	}

	protected function link_resource($resource)
	{
		return sprintf("scherm.php?slide=%s&resource=%s",
			urlencode($this->slide),
			urlencode($resource));
	}

	protected function run_scherm()
	{
		run_view('scherm::scherm', null, null, array('slides' => $this->slides));
	}

	public function run()
	{
		if (isset($_GET['slide']))
		{
			if (!isset($this->slides[$_GET['slide']]))
			{
				header('Status: 400 Not Found');
				echo 'Slide not found';
				return;
			}
	
			$this->slide = $_GET['slide'];
			
			if(isset($_GET['resource']))
				$this->run_resource($_GET['resource']);
			else
				$this->run_slide();
		}
		else
			$this->run_scherm();		
	}
}

$controller = new ControllerScherm(include 'scherm/config.php');
$controller->run();
