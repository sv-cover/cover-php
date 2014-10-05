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

		$this->slides = $this->search_slides($this->search_paths);
	}

	protected function search_slides($search_paths)
	{
		$slides = array();

		foreach ($search_paths as $path => $options)
		{
			foreach (scandir($path) as $folder)
			{
				// Skip dot files
				if ($folder{0} == '.')
					continue;

				// Skip anything not a folder
				if (!is_dir($path . '/' . $folder))
					continue;

				$uid = sha1($path . '/' . $folder);

				$slide = array('path' => $path . '/' . $folder);

				// If it contains a custom slide, add it to the list
				if (file_exists($path . '/' . $folder . '/slide.php') && !empty($options['allow_php']))
					$slide['url'] = $path . '/' . $folder . '/slide.php';

				// If it is just a folder with images, use the default slide
				else if (glob($path . '/' . $folder . '/*.{jpg,png,svg,gif}', GLOB_BRACE))
					$slide['url'] = $this->default_slide;

				// If it is not one of both, just skip it. It is not important.
				else
					continue;

				// If there is a stylesheet, add it to the config.
				if (file_exists($path . '/' . $folder . '/slide.css'))
					$slide['stylesheet'] = $this->link_resource('slide.css', $uid);

				$slides[$uid] = $slide;
			}
		}

		return $slides;
	}

	protected function run_slide()
	{
		chdir($this->slides[$this->slide]['path']);

		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");

		// E-tag to check whether we need to reload
		header('X-Scherm-ETag: ' . $this->generate_etag());
		
		// Proper content type (hopefully)
		header('Content-Type: text/html; charset=' . WEBSITE_ENCODING);

		include $this->slides[$this->slide]['url'];
	}

	protected function run_resource($resource)
	{
		$path = $this->slides[$this->slide]['path'] . '/' . $resource;

		if (!file_exists($path))
		{
			header('Status: 404 Not Found');
			echo 'Resource not found: ' . $path;
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

			case 'svg':
				$mime_type = 'image/svg+xml';
				break;

			case 'css':
				$mime_type = 'text/css';
				break;

			default:
				$mime_type = 'application/octet-stream';
				break;
		}

		// Send the mime type
		header('Content-Type: ' . $mime_type);
		
		// Send the file.
		readfile($path);
	}

	protected function link_resource($resource, $slide = null)
	{
		return sprintf("scherm.php?slide=%s&resource=%s",
			urlencode($slide !== null ? $slide : $this->slide),
			urlencode($resource));
	}

	protected function run_scherm()
	{
		header('Content-Type: text/html; charset=' . WEBSITE_ENCODING);
		run_view('scherm::scherm', null, null, array('slides' => $this->slides));
	}

	protected function generate_etag()
	{
		return md5(implode('', array_keys($this->slides)));
	}

	public function run()
	{
		header('Content-Type: text/html; charset=' . WEBSITE_ENCODING);

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
