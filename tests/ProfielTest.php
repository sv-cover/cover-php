<?php

require_once 'include/init.php';
require_once 'include/test.php';

use PHPUnit\Framework\TestCase;
use \cover\test\Form;

class ProfielTest extends TestCase
{
	use \cover\test\SessionTestTrait;
	
	public function testCanChangePublicFields()
	{
		$new_data = [
			'postcode' => '2222BB',
			'telefoonnummer' => '0612345678',
			'adres' => 'bar',
			'woonplaats' => 'new woonplaats'
		];

		// First get the form (for the nonce)
		$response = $this->simulateRequestWithSession('profiel.php', [
			'GET' => ['lid' => self::$member_id, 'view' => 'personal']
		]);

		$form = Form::fromResponse($response, '//div[@id="personal-tab"]//form[@method="post"]');

		// Merge in the new data
		$form->fields = array_merge($form->fields, $new_data);

		$response = $form->submit([$this, 'simulateRequestWithSession']);

		// If the profile was correctly updated, expect a redirect
		$this->assertEquals(1, preg_match('/^Location: profiel\.php\?lid=' . self::$member_id . '/im', $response->header),
			"Assume we have been redirected to the profile again");

		// Also, the member data should have been updated in the database
		$model = get_model('DataModelMember');

		$member = $model->get_iter(self::$member_id);

		// Assume that the data was correctly reformatted
		$new_data['telefoonnummer'] = '+31612345678';

		foreach ($new_data as $field => $expected_value)
			$this->assertEquals($member[$field], $expected_value,
				"Value of field '{$field}' differs");
	}
}