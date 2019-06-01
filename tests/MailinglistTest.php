<?php

require_once 'include/init.php';
require_once 'include/test.php';

use PHPUnit\Framework\TestCase;
use cover\test\EmailTestTrait;

class MailinglistTest extends TestCase
{
	use EmailTestTrait;

	public function testMailinglistDoesNotExist()
	{
		$result = $this->simulateEmail('testcase@example.com', 'does-not-exist@svcover.nl', 'Hello world!', ['Subject: test-mail']);

		if ($result->exit_code == 255)
			$result->write();

		$this->assertEquals(103, $result->exit_code, 'Expect the script to return the error code RETURN_COULD_NOT_DETERMINE_LIST');

		$this->assertCount(1, $result->messages, 'Expect it also to send a return message to the sender');

		$this->assertEquals($result->messages[0]->sendmail_arg(1), 'testcase@example.com', 'Expect it to be addressed to the sender');
	}
}

