#!/usr/bin/env php
<?php
declare(strict_types=1);

chdir(dirname(__FILE__) . '/..');

require_once 'include/send-mailinglist-mail.php';

use function \Cover\email\mailinglist\get_error_message;
use function \Cover\email\mailinglist\send_mailinglist_mail;

function verbose(int $return_value): int
{
	if ($return_value !== 0)
		fwrite(STDERR, get_error_message($return_value) . "\n");

	return $return_value;
}

function main(): int
{
	// Copy STDIN to buffer stream because we want to stream it to the parser,
	// but we currently also want a copy as string for the database.
	$buffer_stream = fopen('php://temp', 'r+');
	stream_copy_to_stream(STDIN, $buffer_stream);
	$return_value = send_mailinglist_mail($buffer_stream); 
	// Close the buffered message at last
	fclose($buffer_stream);
	return $return_value;
}

exit(verbose(main()));
