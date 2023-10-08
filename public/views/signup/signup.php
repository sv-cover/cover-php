<?php

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

		// Add Unicode byte order marker for Excel

		echo chr(239) . chr(187) . chr(191);
		if (count($entries) === 0)
			return;

		$out = fopen('php://output', 'w');

		// print the column headers
		fputcsv($out, $headers);

		foreach ($entries as $entry)
			fputcsv($out, $entry);
	}
}