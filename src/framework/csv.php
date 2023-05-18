<?php
/** @group CSV
  * Format an array as a CSV (comma separated values) line
  * @fields an array of values to format
  * @delim optional; the delimiter to use
  *
  * @result a CSV string
  */
function csv_row($fields, $delim = ';') {
	$result = '';
	
	foreach ($fields as $field) {
		if ($result != '')
			$result .= $delim;
		
		$field = str_replace('"', '""', $field);
		
		if (strpos($field, $delim) !== false || strpos($field, "\n") !== false)
			$field = '"' . $field . '"';
		
		$result .= $field;
	}
	
	return $result;
}
