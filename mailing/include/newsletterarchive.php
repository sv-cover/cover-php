<?php

class NewsletterArchive
{
	private $path;

	public function __construct($path)
	{
		$this->path = $path;
	}

	public function load($filename)
	{
		if (!preg_match('~^[a-z0-9_\-]+$~i', $filename))
			throw new Exception('Invalid file name');

		$path = $this->path . '/' . $filename . '.bin';

		if (!file_exists($path))
			throw new Exception('File not found');

		$data = file_get_contents($path);

		if (!$data)
			throw new Exception('Could not load file');

		$newsletter = unserialize($data);

		if (!($newsletter instanceof Newsletter))
			throw new Exception('Could not parse newsletter');

		// Mark the newsletter as saved.
		$newsletter->unchanged = true;

		return $newsletter;
	}

	public function save(Newsletter $newsletter, $filename)
	{
		if (!preg_match('~^[a-z0-9_\-]+$~i', $filename))
			throw new Exception('Invalid file name');
		
		$path = $this->path . '/' . $filename . '.bin';

		$newsletter->log('Saved as "' . $filename . '"');

		$data = serialize($newsletter);

		if (!$data)
			throw new Exception('Could not encode newsletter');

		if (!file_put_contents($path, $data))
			throw new Exception('Could not write to file');

		// Mark the newsletter as saved.
		$newsletter->unchanged = true;
	}

	public function listing()
	{
		return glob('*.bin');
	}
}