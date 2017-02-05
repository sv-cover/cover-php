<?php

require_once 'include/init.php';
require_once 'include/test.php';

use PHPUnit\Framework\TestCase;
use cover\test\EmailTestTrait;

class OptInMailinglistTest extends TestCase
{
	use EmailTestTrait;

	private $mailinglist;

	public function setUp()
	{
		$model = get_model('DataModelMailinglist');

		$list = $model->new_iter();

		$nonce = uniqid();

		$list->set_all([
			'naam' => 'testcase_' . $nonce,
			'adres' => 'testcase-' . $nonce . '@svcover.nl',
			'omschrijving' => 'Mailinglist created for test case',
			'type' => DataModelMailinglist::TYPE_OPT_IN,
			'publiek' => new DatabaseLiteral('TRUE'),
			'toegang' => DataModelMailinglist::TOEGANG_DEELNEMERS,
			'commissie' => 0 // board
		]);

		$model->insert($list);

		$this->mailinglist = $list;
	}

	public function tearDown()
	{
		$model = get_model('DataModelMailinglist');

		$model->delete($this->mailinglist);
	}

	public function testGuestSubscribers()
	{
		$this->assertNotNull($this->mailinglist['id']);

		$model = get_model('DataModelMailinglistSubscription');

		$model->subscribe_guest($this->mailinglist, 'Person 1', 'person1@example.com');
		$model->subscribe_guest($this->mailinglist, 'Person 2', 'person2@example.com');
		$model->subscribe_guest($this->mailinglist, 'Person 3', 'person3@example.com');

		$this->assertEquals(3, $model->get_reach($this->mailinglist),
			"Assume that the reach of this mailing list is now 3.");

		$result = $this->simulateEmail('board@svcover.nl', $this->mailinglist['adres'], "Test message");

		$this->assertEquals(0, $result->exit_code, "Mail script should return 0 for a successfully handled message.");

		$this->assertCount(3, $result->messages, "It should send three messages");

		$receivers = array_map(function($message) { return $message->sendmail_arg(1); }, $result->messages);

		$this->assertEquals(['person1@example.com', 'person2@example.com', 'person3@example.com'], $receivers);

		foreach ($result->messages as $message)
		{
			$this->assertEquals($message->header('From'), 'board@svcover.nl', "Message From header should be board@svcover.nl.");

			$this->assertEquals($message->body(), 'Test message', "Message body should be 'test message'.");
		}
	}
}