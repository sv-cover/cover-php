<?php

require_once 'include/init.php';
require_once 'include/test.php';

use PHPUnit\Framework\TestCase;

class ProfielTest extends TestCase
{
	use \cover\test\SessionTestTrait;
	
	public function testCanChangePublicFields()
	{
		$new_data = [
			'postcode' => '2222BB',
			'telefoonnummer' => '0612345678',
			'adres' => 'bar',
			'email' => self::$member_email,
			'woonplaats' => 'new woonplaats'
		];

		// First get the form (for the nonce)
		list($response_header, $response_body) = $this->simulateRequestWithSession('profiel.php', [
			'GET' => ['lid' => self::$member_id, 'view' => 'personal']
		]);

		$response_document = new DOMDocument();
		$response_document->loadHTML($response_body);

		$query = new DOMXPath($response_document);
		$nonce = $query->evaluate('//div[@id="personal-tab"]//form[@method="post"]//input[@name="_nonce"]/@value');

		$post_data = array_merge($new_data, ['_nonce' => $nonce]);

		list($response_header, $response_body) = $this->simulateRequestWithSession('profiel.php', [
			'GET' => ['lid' => self::$member_id, 'view' => 'personal'],
			'POST' => $post_data
		]);

		// If the profile was correctly updated, expect a redirect
		$this->assertEquals(preg_match('/^Location: profiel\.php\?lid=' . self::$member_id . '/im', $response_header), 1);

		// Also, the member data should have been updated in the database
		$model = get_model('DataModelMember');

		$member = $model->get_iter(self::$member_id);

		foreach ($new_data as $field => $expected_value)
			$this->assertEquals($member->get($field), $expected_value);
	}
}