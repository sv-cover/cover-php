<?php namespace Cover\email;

class PeakableStream
{
	protected $stream;

	protected $linebuffer;

	protected $lineNumber;

	public function __construct($stream)
	{
		$this->stream = $stream;

		$this->lineNumber = 0;
	}

	public function peek()
	{
		if ($this->linebuffer === null)
			$this->linebuffer = fgets($this->stream);

		return $this->linebuffer;
	}

	public function readline()
	{
		if ($this->linebuffer !== null) {
			$out = $this->linebuffer;
			$this->linebuffer = null;
		}
		else {
			$out = fgets($this->stream);
		}

		$this->lineNumber += 1;

		return $out;
	}

	public function lineNumber()
	{
		return $this->lineNumber;
	}
}

class MessagePart
{
	protected $headers;

	protected $body;

	const BOUNDARY_LENGTH = 12;

	const TRANSFER_ENCODING_BASE64 = 'base64';

	const TRANSFER_ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

	public function __construct(array $headers = array(), $body = null)
	{
		$this->headers = $headers;

		$this->body = $body;
	}

	public function header($search_key)
	{
		foreach (explode('|', $search_key) as $key)
		{
			// Short-cut
			if (isset($this->headers[$key]))
				return $this->decodeHeader($this->headers[$key]);

			// Case-insensitive search
			foreach ($this->headers as $header_key => $value)
				if (strcasecmp($key, $header_key) === 0)
					return $this->decodeHeader($value);
		}

		return null;
	}

	public function setHeader($key, $value)
	{
		$this->headers[$key] = $this->encodeHeaderIfNeeded($value);
	}

	public function isMultipart()
	{
		return is_array($this->body);
	}

	public function body($preferred_content_type)
	{
		// If this is a simple part (no multipart) just return the data
		if (!$this->isMultipart())
			return $this->decodeBody($this->body, $this->header('Content-Transfer-Encoding'));

		// However, if this is a multipart message, search all the sub parts for the preferred content type
		foreach ($this->body as $part)
			if ($part->hasBodyOfType($preferred_content_type))
				return $part->body($preferred_content_type);

		// And return null if it could not be found
		return null;
	}

	public function hasBodyOfType($content_type)
	{
		// Am I a body of the content type? (And thus probably not multipart)
		if (preg_match('/^' . preg_quote($content_type, '/') . '(;\s*charset=(.+?))?$/i', $this->header('Content-Type')))
			return true;

		// Or if I am multipart, is one of my sections of the type?
		if ($this->isMultipart())
			foreach ($this->parts as $part)
				if ($part->hasBodyOfType($content_type))
					return true;

		return false;
	}

	protected function encodeHeader($data)
	{
		return '=?' . base64_encode($data) . '?B?UTF-8?=';
	}

	protected function encodeHeaderIfNeeded($data)
	{
		if (preg_match('/^[[:ascii:]]*$/', $data))
			return $data;

		return $this->encodeHeader($data);
	}

	protected function decodeHeader($data)
	{
		$decode = function($match) {
			switch ($match[2])
			{
				case 'Q':
					$data = quoted_printable_decode($match[3]);
					break;

				case 'B':
					$data = base64_decode($data);
					break;
			}

			if (strcasecmp($match[1], 'utf-8') !== 0)
				$data = iconv($match[1], 'UTF-8//TRANSLIT', $data);

			return $data;
		};

		return preg_replace_callback('/=\?([a-zA-Z0-9_-]+)\?(Q|B)\?(.+?)\?=/', $decode, $data);
	}

	protected function encodeBody($data, $transfer_encoding)
	{
		switch (strtolower($transfer_encoding))
		{
			case self::TRANSFER_ENCODING_QUOTED_PRINTABLE:
				return quoted_printable_encode($data);

			case self::TRANSFER_ENCODING_BASE64:
				return base64_encode($data);

			default:
				throw new InvalidArgumentException('Encoding for this Content-Transfer-Encoding is not supported');
		}
	}

	protected function decodeBody($data, $transfer_encoding = null)
	{
		switch (strtolower($transfer_encoding))
		{
			case 'quoted-printable':
				return quoted_printable_decode($data);

			case 'base64':
				return base64_decode($data);

			default:
				return $data;

			// For 7bit encoding see http://www.jugbit.com/php/encoding-ascii-to-7bit-strings-and-decoding/
		}
	}

	public function addBody($content_type, $body, $content_transfer_encoding = null)
	{
		assert('is_string($body)');

		// No previous body was set, this is the part :O
		if ($this->body === null) {
			$part = $this;
		}
		else {
			$part = new MessagePart();
			$this->addPart($part);
		}
		
		$part->setHeader('Content-Type', $content_type);

		if (preg_match('/^text\/html/', $content_type) && $content_transfer_encoding === null)
			$content_transfer_encoding = self::TRANSFER_ENCODING_QUOTED_PRINTABLE;

		if ($content_transfer_encoding !== null) {
			$body = $this->encodeBody($body, $content_transfer_encoding);
			$part->setHeader('Content-Transfer-Encoding', $content_transfer_encoding);
		}

		$part->body = $body;
	}

	public function addPart(MessagePart $part)
	{
		if (!$this->isMultipart())
			$this->makeMultipart();

		$this->body[] = $part;
	}

	private function makeMultipart()
	{
		assert('is_string($this->body)');

		$this->body = [new MessagePart(['Content-Type' => $this->header('Content-Type')], $this->body)];
		
		$this->setHeader('Content-Type', 'multipart/alternative; boundary=' . $this->generateBoundary());
	}

	private function generateBoundary()
	{
		$out = '';

		$letters = range('a', 'z');

		for ($i = 0; $i < self::BOUNDARY_LENGTH; ++$i)
			$out .= $letters[mt_rand(0, count($letters) - 1)];

		return $out;
	}

	private function boundary()
	{
		if (preg_match('/^multipart\/.+?;\s*boundary=([^;]+?)(;|$)/', $this->header('Content-Type'), $match))
			return $match[1];

		return null;
	}

	private function breakLine($line, $preferred_length, $max_length, $split_pattern = '/[[:space:],-:]/')
	{
		if (strlen($line) <= $preferred_length)
			return [$line, ''];
		elseif (preg_match($split_pattern, $line, $match, PREG_OFFSET_CAPTURE, $preferred_length))
			return [substr($line, 0, $match[0][1]), substr($line, $match[0][1])];
		elseif (strlen($line) <= $max_length)
			return [$line, ''];
		else
			return [substr($line, 0, $max_length), substr($line, $max_length + 1)];
	}

	private function wrapLines($text, $preferred_length, $max_length, $prefix = '')
	{
		$lines = preg_split('/\r?\n/', $text);

		if (!is_callable($prefix))
			$prefix = function() use ($prefix) {
				return $prefix;
			};

		for ($i = 0; $i < count($lines); ++$i)
		{
			$line_prefix = $prefix($i);

			$line_preferred_length = $preferred_length - strlen($line_prefix);

			$line_max_length = $max_length - strlen($line_prefix);

			list($line, $next_line) = $this->breakLine($lines[$i], $line_preferred_length, $line_max_length);

			$lines[$i] = $line_prefix . $line;

			if ($next_line != '')
			{
				if (!isset($lines[$i + 1]) || preg_match('/^\s*$/', $lines[$i + 1]))
					array_splice($lines, $i + 1, 0, [$next_line]);
				else
					$lines[$i + 1] = $next_line . ' ' . $lines[$i + 1];
			}
		}

		return implode("\r\n", $lines);
	}

	public function toString()
	{
		$out = '';
		
		$header_indent = str_repeat(" ", 8);

		foreach ($this->headers as $key => $value)
			$out .= $this->wrapLines($value, 78, 998,
				function ($i) use ($key, $header_indent) {
					if ($i === 0) return $key . ': ';
					else return $header_indent;
				}) . "\r\n";
		
		$out .= "\r\n";

		if (!$this->isMultipart())
		{
			$out .= $this->wrapLines($this->body, 78, 998);
		}
		else
		{
			$boundary = $this->boundary();

			if (!$boundary)
				throw new RuntimeException('Could not parse boundary string out of the Content-Type header');

			foreach ($this->body as $part)
			{
				$out .= "\r\n--$boundary\r\n";
				$out .= $part->toString();
			}

			$out .= "\r\n--$boundary--\r\n";
		}

		return $out;
	}
}

function parse_stream(PeakableStream $stream, $parent_boundary = null)
{
	$headers = parse_header($stream);

	if (isset($headers['Content-Type']) && preg_match('/^multipart\/.+?;\s*boundary=([^;]+?)(;|$)/', $headers['Content-Type'], $match))
		$body = parse_multipart_body($stream, $match[1]);
	else
		$body = parse_plain_body($stream, $parent_boundary);

	return  new MessagePart($headers, $body);
}

function parse_text($raw_message)
{
	$tmp_stream = fopen('php://temp', 'r+');
	fwrite($tmp_stream, $raw_message);
	rewind($tmp_stream);

	return parse_stream(new PeakableStream($tmp_stream));
}

function parse_header(PeakableStream $stream)
{
	$headers = array();

	$current_header = null;

	while (true)
	{
		$line = $stream->readline();

		// Newline? Oh god end of headers!
		if (preg_match('/^(\r?\n|\r)$/', $line))
			break;
		
		elseif (preg_match('/^([^:\s]+[^:]*): ?(.*)$/', $line, $match))
			$headers[$current_header = $match[1]] = trim($match[2]);

		elseif ($current_header !== null)
			$headers[$current_header] .= "\n" . trim($line);
	}

	return $headers;
}

function parse_multipart_body(PeakableStream $stream, $boundary)
{
	$parts = array();

	while (true)
	{
		$line = $stream->readline();

		if ($line === false)
			throw new ParseException("Unexpected end of stream");

		if (trim($line) == '--' . $boundary . '--')
			break;

		elseif (trim($line) === '--' . $boundary)
		{
			$parts[] = parse_stream($stream, $boundary);
		}

		else
			throw new RuntimeException('Could not parse ' . $stream->lineNumber());
	}

	return $parts;
}

function parse_plain_body(PeakableStream $stream, $boundary)
{
	$body = '';

	while (true)
	{
		$line = $stream->peek();

		// End of stream
		if ($line === false)
			break;

		// End of this multipart section
		if ($boundary !== null && (trim($line) == '--' . $boundary . '--' || trim($line) === '--' . $boundary))
			break;
		
		$body .= $stream->readline();
	}

	return $body;
}

function break_line($line, $max_length)
{
	$last_space = strrpos($line, ' ', $max_length - strlen($line));

	if ($last_space === false)
		return [substr($line, 0, $max_length), substr($line, $max_length)];
	else
		return [substr($line, 0, $last_space), substr($line, $last_space + 1)];
}

function quote_plain_text($text_body, $line_wrap = 78)
{
	$lines = [];

	foreach (explode("\n", $text_body) as $line)
		if (preg_match('/^(>+)\s*(.*)$/', $line, $match))
			$lines[] = array(rtrim($match[2]), strlen($match[1]) + 1);
		else
			$lines[] = array(rtrim($line), 1);

	for ($i = 0; $i < count($lines); ++$i)
	{
		list($line, $indent) = $lines[$i];

		// Line plus indent signs (old plus a new one) plus a spacev
		if (strlen($line) + $indent + 1 > $line_wrap) {
			list($line, $next_line) = break_line($line, $line_wrap - $indent - 1);
			$lines[$i][0] = $line;

			if (isset($lines[$i + 1]) && $lines[$i + 1][1] == $indent && !preg_match('/^\s*$/', $lines[$i + 1][0]))
				$lines[$i + 1][0] = $next_line . ' ' . $lines[$i + 1][0];
			else
				array_splice($lines, $i + 1, 0, [[$next_line, $indent]]);
		}
	}

	$out = '';

	foreach ($lines as $line)
		$out .= str_repeat('>', $line[1]) . ' ' . $line[0] . "\r\n";

	return $out;
}

function reply(MessagePart $message, $reply_text, $additional_headers)
{
	$receipient = $message->header('Sender|From');

	$reply = new MessagePart();

	foreach ($additional_headers as $key => $value)
		$reply->setHeader($key, $value);

	$reply->setHeader('Subject', (preg_match('/^Re: /i', $message->header('Subject')) ? '' : 'Re: ') . $message->header('Subject'));
	$reply->setHeader('To', $receipient);

	if ($message_id = $message->header('Message-ID'))
	{
		$reply->setHeader('In-Reply-To', $message_id);

		if ($prev_references = $message->header('References'))
			$reply->setHeader('References', $message_id . "\n" . $prev_references);
		else
			$reply->setHeader('References', $message_id);
	}

	$text_reply = $reply_text;

	if ($text_body = $message->body('text/plain'))
		$text_reply .= "\n\n" . quote_plain_text($text_body);

	$reply->addBody('text/plain; charset=UTF-8', $text_reply);

	if ($message->isMultipart())
	{
		if ($html_body = $message->body('text/html'))
		{
			$html_reply = sprintf('<p>%s</p><blockquote style="margin:0 0 0 0.8ex; border-left: 1px #ccc solid; padding-left: 1ex">%s</blockquote>',
				nl2br(htmlspecialchars($reply_text)),
				$html_body);

			$reply->addBody('text/html; chartype=UTF-8', $html_reply);
		}
	}

	return $reply;
}

if (realpath($_SERVER['PWD'] . '/' . $_SERVER['PHP_SELF']) == __FILE__)
{
	fgets(STDIN);

	// $message = parse_stream(new PeakableStream(STDIN));

	// var_dump($message);

	// echo "\n\n\n";

	// echo $message->toString();

	echo reply(stream_get_contents(STDIN),
		'This is the reply. Very plain and simple.',
		['From' => 'WebCie Mail Monkey <webcie@svcover.nl>']);
}