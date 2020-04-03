<pre>
<?php

include 'include/email.php';

use Cover\email\MessagePart;
use Cover\email\PeakableStream;
use Cover\email\send;

$fh = fopen('mailtje.eml', 'rb');

$message = MessagePart::parse_stream(new PeakableStream($fh));

fclose($fh);

$fout = fopen('mailtje2.eml', 'wb');
fwrite($fout, $message->toString());
fclose($fout);

Cover\email\send($message);

?>
</pre>
