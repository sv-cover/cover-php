<?php

require_once 'include/init.php';
require_once 'include/test.php';

use PHPUnit\Framework\TestCase;
use cover\test\EmailTestTrait;
use cover\test\MemberTestTrait;

class OptInMailinglistTest extends TestCase
{
	use EmailTestTrait;
	use MemberTestTrait;

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

			$this->assertEquals(trim($message->body()), 'Test message', "Message body should be 'test message'.");
		}
	}

	public function testMemberSubscribers()
	{
		$member = get_model('DataModelMember')->get_iter(self::$member_id);

		$model = get_model('DataModelMailinglistSubscription');

		$model->subscribe_member($this->mailinglist, $member);

		$this->assertEquals(1, $model->get_reach($this->mailinglist),
			"Assume that the reach of this mailing list is now 1.");

		$result = $this->simulateEmail('board@svcover.nl', $this->mailinglist['adres'], "Test message");

		$this->assertEquals(0, $result->exit_code, "Mail script should return 0 for a successfully handled message.");

		$this->assertCount(1, $result->messages, "It should send a single message");

		$this->assertEquals($result->messages[0]->sendmail_arg(1), self::$member_email, "Message should be send to the member.");
	}

	public function testGuestPlaceholder()
	{
		$model = get_model('DataModelMailinglistSubscription');

		$model->subscribe_guest($this->mailinglist, 'Person X', 'person1@example.com');
		
		$result = $this->simulateEmail('board@svcover.nl', $this->mailinglist['adres'], "Hello [NAME] please receive this [MAILINGLIST] message");

		$this->assertCount(1, $result->messages, "It should send a single message");

		$this->assertEquals(trim($result->messages[0]->body()), "Hello Person X please receive this {$this->mailinglist['naam']} message");
	}

	public function testMemberPlaceholder()
	{
		$member = get_model('DataModelMember')->get_iter(self::$member_id);

		$model = get_model('DataModelMailinglistSubscription');

		$model->subscribe_member($this->mailinglist, $member);
		
		$result = $this->simulateEmail('board@svcover.nl', $this->mailinglist['adres'], "Hello [NAME] please receive this [MAILINGLIST] message");

		$this->assertCount(1, $result->messages, "It should send a single message");

		$this->assertEquals(trim($result->messages[0]->body()), "Hello Unit please receive this {$this->mailinglist['naam']} message");
	}

	public function testGuestUnsubscribeLink()
	{
		$model = get_model('DataModelMailinglistSubscription');

		$model->subscribe_guest($this->mailinglist, 'Person X', 'person1@example.com');
		
		$result = $this->simulateEmail('board@svcover.nl', $this->mailinglist['adres'], "Click to [UNSUBSCRIBE_URL]");

		$this->assertCount(1, $result->messages, "It should send a single message");

		// Find the unsubscribe link

		$url = substr(trim($result->messages[0]->body()), strlen("Click to "));

		$this->assertStringStartsWith(ROOT_DIR_URI, $url);

		// Take the relative part to simulate it locally

		$url_relative = substr($url, strlen(ROOT_DIR_URI));

		$this->assertStringStartsWith('mailinglijsten.php', $url_relative);

		parse_str(parse_url($url_relative, PHP_URL_QUERY), $query);

		// Fetch the unsubscribe form

		$response = \cover\test\simulate_request('mailinglijsten.php', ['GET' => $query]);

		$form = \cover\test\Form::fromResponse($response, '//div[@class="messageBox"]//form[@method="post"]');

		// Click on the submit button

		$response = $form->submit();

		// Assume we have unsubscribed

		$this->assertEquals(0, $model->get_reach($this->mailinglist), "Assume the reach of the mailing list is now back to 0.");

		$result = $this->simulateEmail('board@svcover.nl', $this->mailinglist['adres'], "Test message again");

		$this->assertCount(0, $result->messages, "Assume that when a message gets send to the mailing list, we won't receive it anymore.");
	}
}