<?php

namespace fields;

class Address implements \SignUpFieldType
{
	public $name;
	
	public $required;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;
	}

	public function configuration()
	{
		return [
			'required' => (bool) $this->required
		];
	}

	public function process(array $post_data, &$error)
	{
		$address = trim($post_data[$this->name . '-address'] ?? '');

		$city = trim($post_data[$this->name . '-city'] ?? '');

		if ($this->required && ($address == '' || $city == ''))
			$error = __('Value required');

		return json_encode(compact('address', 'city'));
	}

	public function suggest(\DataIterMember $member)
	{
		return json_encode([
			'address' => $member['adres'],
			'city' => $member['woonplaats']
		]);
	}

	public function render($renderer, $value, $error)
	{
		if ($value !== null)
			$data = json_decode($value, true);
		else
			$data = [];

		return $renderer->render('@form_fields/address.twig', [
			'name' => $this->name,
			'configuration' => $this->configuration(),
			'data' => [
				$this->name . '-address' => $data['address'] ?? '',
				$this->name . '-city' => $data['city'] ?? ''
			],
			'errors' => $error ? [
				$this->name . '-address' => $error,
				$this->name . '-city' => $error
			] : []
		]);
	}

	public function process_configuration(array $post_data, \ErrorSet $errors)
	{
		$this->required = !empty($post_data['required']);
		return true;
	}

	public function render_configuration($renderer, \ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/address.twig', [
			'data' => $this->configuration(),
			'errors' => $errors
		]);
	}

	public function export($value)
	{
		$defaults = ['address' => '', 'city' => ''];
		return array_merge($defaults, array_intersect_key((array) json_decode($value, true), $defaults));
	}
}