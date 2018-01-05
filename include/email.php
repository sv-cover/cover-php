<?php

namespace Cover\email;

class ParseException extends \RuntimeException
{
	protected $bodyLine;

	public function __construct($line, $body = null, $previous = null)
	{
		parent::__construct(self::formatMessage($line, $body), 0, $previous);
	}

	static private function formatMessage($line, $email = null)
	{
		$body = "Parse error on line $line";
		
		if ($email !== null) {
			$offset = max(0, $line - 5);
			$lines = explode("\n", $email);
			$lines = array_slice($lines, $offset, 11);
				
			for ($n = 0; $n < count($lines); ++$n)
				$lines[$n] = sprintf('%d: %s', $offset + $n + 1, $lines[$n]);

			$body .= "\nContext: " . implode("\n", $lines);
		}

		return $body;
	}

	public function withMessage($message)
	{
		return new self($this->bodyLine, $message, $this);
	}
}

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

	const TRANSFER_ENCODING_7BIT = '7bit';

	const TRANSFER_ENCODING_BASE64 = 'base64';

	const TRANSFER_ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

	public function __construct(array $headers = array(), $body = null)
	{
		$this->headers = $headers;

		$this->body = $body;
	}

	public function headers($search_key = null)
	{
		if ($search_key === null)
			return array_map(function($values) {
				return array_map([$this, 'decodeHeader'], $values);
			}, $this->headers);

		$keys = explode('|', $search_key);

		if (count($keys) > 1) {
			foreach ($keys as $key)
				if ($headers = $this->headers($key))
					return $headers;
			return [];
		}

		// Short-cut
		if (isset($this->headers[$search_key]))
			return array_map([$this, 'decodeHeader'], $this->headers[$search_key]);

		// Case-insensitive search
		foreach ($this->headers as $header_key => $values)
			if (strcasecmp($search_key, $header_key) === 0)
				return array_map([$this, 'decodeHeader'], $values);

		return [];
	}

	public function header($search_key)
	{
		$headers = $this->headers($search_key);
		return $headers === [] ? null : $headers[0];
	}

	public function setHeader($key, $value)
	{
		$this->headers[$key] = [$this->encodeHeaderIfNeeded($value)];
	}

	public function addHeader($key, $value)
	{
		if (isset($this->headers[$key]))
			$this->headers[$key][] = $this->encodeHeaderIfNeeded($value);
		else
			$this->setHeader($key, $value);
	}

	public function removeHeader($key)
	{
		unset($this->headers[$key]);
	}

	public function isMultipart()
	{
		return is_array($this->body);
	}

	public function parts()
	{
		return $this->isMultipart() ? $this->body : [$this->body];
	}

	/**
	 * @return MessagePart[] all message parts that make up the displayable content (plain/text, html, not attachment)
	 */
	public function messageParts()
	{
		foreach ($this->parts() as $part)
		{
			if ($part->isAttachment())
				continue;

			if ($part->hasContentType('text/html') || $part->hasContentType('text/plain'))
				yield $part;

			if ($part->isMultipart())
				yield from $part->messageParts();
		}
	}

	public function body($preferred_content_type = null)
	{
		// If this is a simple part (no multipart) just return the data
		if (!$this->isMultipart())
			return $this->decodeBody($this->body,
				$this->header('Content-Transfer-Encoding'),
				charset($this->header('Content-Type')));

		// However, if this is a multipart message, search all the sub parts for the preferred content type
		foreach ($this->body as $part)
			if ($preferred_content_type === null || $part->hasBodyOfType($preferred_content_type))
				return $part->body($preferred_content_type);

		// And return null if it could not be found
		return null;
	}

	public function hasBodyOfType($content_type)
	{
		// Am I a body of the content type? (And thus probably not multipart)
		if ($this->hasContentType($content_type))
			return true;

		// Or if I am multipart, is one of my sections of the type?
		if ($this->isMultipart())
			foreach ($this->body as $part)
				if ($part->hasBodyOfType($content_type))
					return true;

		return false;
	}

	public function hasContentType($content_type)
	{
		return preg_match(
			'/^' . preg_quote($content_type, '/') . '(;\s*charset=(.+?))?$/i',
			$this->header('Content-Type'));
	}

	public function isAttachment()
	{
		return preg_match(
			'/^attachment(;.+)?$/i',
			$this->header('Content-Disposition'));
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
					$data = base64_decode($match[3]);
					break;
			}

			if (strcasecmp($match[1], 'utf-8') !== 0)
				$data = iconv($match[1], 'UTF-8//TRANSLIT', $data);

			return $data;
		};

		return preg_replace_callback('/=\?([a-zA-Z0-9_-]+)\?(Q|B)\?(.+?)\?=/', $decode, $data);
	}

	protected function encodeBody($data, $transfer_encoding, $charset)
	{
		switch (strtolower($transfer_encoding))
		{
			case self::TRANSFER_ENCODING_QUOTED_PRINTABLE:
				return quoted_printable_encode($data);

			case self::TRANSFER_ENCODING_BASE64:
				return base64_encode($data);

			case self::TRANSFER_ENCODING_7BIT:
				return mb_convert_encoding($data, $charset, 'auto');

			default:
				throw new \InvalidArgumentException('Encoding for this Content-Transfer-Encoding (' . $transfer_encoding . ') is not supported');
		}
	}

	protected function decodeBody($data, $transfer_encoding = null, $charset = null)
	{
		switch (strtolower($transfer_encoding))
		{
			case self::TRANSFER_ENCODING_QUOTED_PRINTABLE:
				return quoted_printable_decode($data);

			case self::TRANSFER_ENCODING_BASE64:
				return base64_decode($data);

			case self::TRANSFER_ENCODING_7BIT:
				return mb_convert_encoding($data, 'UTF-8', $charset ?: 'auto');

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
		
		$part->setBody($body, $content_type, $content_transfer_encoding);
	}

	public function setBody($body, $content_type = null, $content_transfer_encoding = null)
	{
		if ($content_type === null)
			$content_type = $this->header('Content-Type');

		if ($content_transfer_encoding === null)
			$content_transfer_encoding = $this->header('Content-Transfer-Encoding');

		$this->setHeader('Content-Type', $content_type);

		if (preg_match('/^text\//', $content_type) && $content_transfer_encoding === null)
			$content_transfer_encoding = self::TRANSFER_ENCODING_QUOTED_PRINTABLE;

		if ($content_transfer_encoding !== null) {
			$body = $this->encodeBody($body, $content_transfer_encoding, charset($content_type));
			$this->setHeader('Content-Transfer-Encoding', $content_transfer_encoding);
		}

		$this->body = $body;
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

		if ($this->body !== null)
			$this->body = [new MessagePart(['Content-Type' => [$this->header('Content-Type')]], $this->body)];
		else
			$this->body = [];
		
		if (!$this->header('Content-Type') || $this->boundary() === null)
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
		if (preg_match('/^multipart\/.+?;\s*boundary=("?)([^;]+?)(\1)(;|$)/', $this->header('Content-Type'), $match))
			return $match[2];

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

	public function headerAsString()
	{
		$out = '';

		$header_indent = str_repeat(" ", 8);

		foreach ($this->headers as $key => $values)
		{
			foreach ($values as $value)
				$out .= $this->wrapLines($value, 78, 998,
						function ($i) use ($key, $header_indent) {
							if ($i === 0) return $key . ': ';
							else return $header_indent;
						}) . "\r\n";
		}

		return $out;
	}

	public function bodyAsString()
	{
		if (!$this->isMultipart())
		{
			$out = $this->wrapLines($this->body, 78, 998);
		}
		else
		{
			$out = '';

			$boundary = $this->boundary();

			if (!$boundary)
				throw new \RuntimeException('Could not parse boundary string out of the Content-Type header');

			foreach ($this->body as $part)
			{
				if (substr($out, -2, 2) != "\r\n")
					$out .= "\r\n";

				$out .= "--$boundary\r\n";
				$out .= $part->toString();
			}

			$out .= "\r\n--$boundary--\r\n";
		}

		return $out;
	}

	public function toString()
	{
		return $this->headerAsString() . "\r\n" . $this->bodyAsString();
	}

	static public function parse_stream(PeakableStream $stream, $parent_boundary = null)
	{
		$message = new self();

		self::parse_header($stream, $message);

		if ($message->header('Content-Type') && preg_match('/^multipart\/.+?;\s*boundary=("?)([^;]+?)(\1)(;|$)/', $message->header('Content-Type'), $match))
			self::parse_multipart_body($stream, $match[2], $message);
		else
			self::parse_plain_body($stream, $parent_boundary, $message);

		return $message;
	}

	static public function parse_text($raw_message)
	{
		$tmp_stream = fopen('php://temp', 'r+');
		fwrite($tmp_stream, $raw_message);
		rewind($tmp_stream);

		try {
			return self::parse_stream(new PeakableStream($tmp_stream));
		} catch (ParseException $e) {
			throw $e->withMessage($raw_message);
		}
	}

	/**
	 * Parse a stream as the email message header. Will stop as 
	 * soon as the double newline is read.
	 * 
	 * @param $stream PeakableStream to read from.
	 * @param $message MessagePart to append the read headers to.
	 * @return true if the headers are read as expected, false if the
	 * stream ends while reading the header.
	 */
	static public function parse_header(PeakableStream $stream, MessagePart $message)
	{
		$header = null;

		while (true)
		{
			$line = $stream->readline();

			// False? That is unexpected..
			if ($line === false)
			{
				return false;
			}

			// Newline? Oh god end of headers!
			elseif (preg_match('/^(\r?\n|\r)$/', $line))
			{
				if ($header !== null)
					$message->addHeader($header[0], $header[1]);
				
				break;
			}
			
			elseif (preg_match('/^([^:\s]+[^:]*): ?(.*)$/', $line, $match))
			{
				if ($header !== null)
					$message->addHeader($header[0], $header[1]);

				$header = [$match[1], trim($match[2])];
			}

			elseif ($header !== null)
			{
				$header[1] .= " " . trim($line);
			}
		}

		return true;
	}

	static private function parse_multipart_body(PeakableStream $stream, $boundary, MessagePart $message)
	{
		$message->body = [];

		while (true)
		{
			$line = $stream->readline();

			if ($line === false)
				throw new \RuntimeException("Unexpected end of stream");

			if (trim($line) === '')
				continue;

			if (trim($line) === '--' . $boundary . '--')
				break;

			elseif (trim($line) === '--' . $boundary)
				$message->body[] = self::parse_stream($stream, $boundary);

			else
				throw new ParseException($stream->lineNumber());
		}
	}

	static private function parse_plain_body(PeakableStream $stream, $boundary, MessagePart $message)
	{
		while (true)
		{
			$line = $stream->peek();

			// End of stream
			if ($line === false)
				break;

			// End of this multipart section
			if ($boundary !== null && (trim($line) == '--' . $boundary . '--' || trim($line) === '--' . $boundary))
				break;
			
			$message->body .= $stream->readline();
		}
	}
}

function charset($content_type)
{
	// E.g. "text/html; charset=us-ascii"
	return preg_match('/^text\/.+;\s*charset=([A-Z0-9-]+?)(;|$)/i', $content_type, $match) ? $match[1] : null;
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

function reply(MessagePart $message, $reply_text)
{
	$receipient = $message->header('Sender|From');

	$reply = new MessagePart();

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

function personalize(MessagePart $message, callable $text_filter)
{
	foreach ($message->messageParts() as $part)
		$part->setBody($text_filter($part->body()));
}

function send(MessagePart $message)
{
	$to = $message->header('To');
	$message->removeHeader('To');

	$subject = $message->header('Subject');
	$message->removeHeader('Subject');
	
	return mail($to, $subject,
		$message->bodyAsString(),
		$message->headerAsString());
}

if (isset($_SERVER['PWD']) && realpath($_SERVER['PWD'] . '/' . $_SERVER['PHP_SELF']) == __FILE__)
	var_dump(MessagePart::parse_stream(new PeakableStream(STDIN))->toString());