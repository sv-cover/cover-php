#!/usr/bin/env php
<?php
ini_set('memory_limit', '512M');
chdir(dirname(__FILE__) . '/..');

require_once 'include/init.php';
require_once 'include/terminal.php';

$photo_model = get_model('DataModelFotoboek');

$options = array(
	'force' => false, 
	'recursive' => false,
	'workers' => 0,
	'slave' => false,
	'verbose' => false
);

$help = array(
	'force' => 'Force generation of new thumbnails, even if they already exist and the image has not been changed.',
	'recursive' => 'Descend into child photo books.',
	'workers' => 'Number of workers to spawn to divide the load. If 0 this program will act as a worker.',
	'slave' => 'Force the program in slave modus, processing any photo id passed through stdin.',
	'verbose' => 'Print all output of the workers'
);

$book_ids = parse_options($argv, $options, $help);

function array_flatten($arrays)
{
	if (!$arrays)
		return array();

	return call_user_func_array('array_merge', $arrays);
}

function get_book_photos($book_id)
{
	global $photo_model;
	return $photo_model->get_book($book_id)->get_photos();
}

function get_book_photos_recursive($book_id)
{
	global $photo_model;

	if ($book_id instanceof DataIterPhotobook)
		$book = $book_id;
	else
		$book = $photo_model->get_book($book_id);

	return array_merge($book->get_photos(),
			array_flatten(array_map('get_book_photos_recursive', $book->get_books())));
}

class Worker
{
	public $process;

	public $pipes;

	public $id;

	public function __construct()
	{
		static $n = 0;
		$this->id = "w" . $n++;
	}
}

function main_master()
{
	global $book_ids, $options, $argv;

	$photos = array_flatten(array_map($options['recursive'] ? 'get_book_photos_recursive' : 'get_book_photos', $book_ids));
	
	printf("Starting %d workers for %d photos...\n", $options['workers'], count($photos));

	$workers = array();

	$task_buckets = array_chunk($photos, count($photos) / $options['workers']);

	$photos_processed = 0;

	$photos_total = count($photos);

	unset($photos);

	for ($worker_index = 0; $worker_index < $options['workers']; ++$worker_index)
	{
		$worker = new Worker();

		$descriptor_spec = array(
			0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
			1 => array('pipe', 'w'),  // stdout is a pipe that the child will write to
			2 => array('file', 'php://stderr', 'a') // stderr is a file to write to
		);

		$worker_options = array('--slave');

		if ($options['force'])
			$worker_options[] = '--force';

		$worker->process = proc_open('php ' . $argv[0] . ' ' . implode(' ', $worker_options), $descriptor_spec, $worker->pipes);

		fwrite($worker->pipes[0], implode("\n", array_map(function($photo) { return $photo->get_id(); }, $task_buckets[$worker_index])));

		// Close stdin after writing all photo ids
		fclose($worker->pipes[0]);

		// Make stdout of process non-blocking
		stream_set_blocking($worker->pipes[1], 0);

		$workers[$worker->id] = $worker;
	}

	$workers_still_busy = true;

	$pipes_to_poll = array();

	foreach ($workers as $worker)
		$pipes_to_poll[$worker->id] = $worker->pipes[1];

	while (count($pipes_to_poll) > 0)
	{
		$n_of_updates = stream_select($pipes_polled = $pipes_to_poll, $w = null, $e = null, null);

		// Is something wrong?
		if ($n_of_updates === false)
			throw new Exception("stream_select returned false");

		// Or is this just a timeout?
		else if ($n_of_updates === 0)
			continue;

		// else, we have work to do!
		foreach ($pipes_polled as $pipe)
		{
			$worker_id = array_search($pipe, $pipes_to_poll);
			$worker = $workers[$worker_id];
			
			while ($line = fgets($worker->pipes[1]))
			{
				if ($options['verbose'])
					printf("Worker %s: %s\n", $worker->id, rtrim($line, "\n"));

				++$photos_processed;
			}

			// If this worker is done, remove it from the list of pipes to poll
			if (feof($worker->pipes[1]))
				unset($pipes_to_poll[$worker->id]);
		}

		if (!$options['verbose'])
			printf("%s% 2d%%", str_repeat(chr(8), 3), floor($photos_processed / $photos_total * 100));
	}

	echo str_repeat(chr(8), 3) . "Done.\n";
}

function main_slave()
{
	global $photo_model, $options;

	$photo_ids = array();

	while (!feof(STDIN)) {
		fscanf(STDIN, "%d\n", $id);
		$photo_ids[] = $id;
	}

	foreach ($photo_ids as $photo_id)
	{
		$photo = $photo_model->get_iter($photo_id);

		foreach (get_config_value('precomputed_photo_scales', array()) as $dimesions)
			fclose($photo->get_resource($dimesions[0], $dimesions[1], $options['force']));
			
		printf("%d\n", $photo->get_id());
	}
}

function main()
{
	global $book_ids, $options;
	
	$photos = array_flatten(array_map($options['recursive'] ? 'get_book_photos_recursive' : 'get_book_photos', $book_ids));

	printf("Processing %d photos...\n", count($photos));

	$photos_processed = 0;

	foreach ($photos as $photo)
	{
		foreach (get_config_value('precomputed_photo_scales', array()) as $dimesions)
			fclose($photo->get_resource($dimesions[0], $dimesions[1], $options['force']));

		++$photos_processed;
			
		printf("%s% 2d%%", str_repeat(chr(8), 3), floor($photos_processed / count($photos) * 100));
	}

	echo str_repeat(chr(8), 3) . "Done.\n";
}

if ($options['slave'])
	main_slave();
elseif ($options['workers'] > 1)
	main_master();
else
	main();