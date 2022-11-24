<?php

require_once 'src/framework/csv.php';

class SignUpView extends View
{
	protected $__file = __FILE__;

	public function render_csv(array $entries, array $headers, $filename = null)
	{
		if ($filename) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/force-download');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		if (count($entries) === 0)
			return;
		
		$delim = ','; // Used to be ';'
		$lb = "\r\n"; // Line break

		// Add Unicode byte order marker for Excel
		echo chr(239) . chr(187) . chr(191);

		// print the column headers
		echo csv_row($headers, $delim), $lb;

		foreach ($entries as $entry)
			echo csv_row($entry, $delim), $lb;
	}
}