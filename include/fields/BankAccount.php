<?php

namespace fields;

class BankAccount implements \SignUpFieldType
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
		$iban = trim($post_data[$this->name . '-iban'] ?? '');

		$iban = preg_replace('/[^A-Z0-9]/u', '', strtoupper($iban));

		$bic = trim($post_data[$this->name . '-bic'] ?? '');

		if ($this->required && $iban == '')
			$error = __('Value required');

		if ($iban != '' && !\IsoCodes\Iban::validate($iban))
			$error = __('Invalid IBAN');

		if ($bic != '' && \IsoCodes\SwiftBic::validate($bic))
			$error = __('Invalid BIC code');

		return json_encode(compact('iban', 'bic'));
	}

	public function render($renderer, $value, $error)
	{
		$data = \json_decode($value, true);

		return $renderer->render('@form_fields/bankaccount.twig', [
			'name' => $this->name,
			'configuration' => $this->configuration(),
			'data' => [
				$this->name . '-iban' => $data['iban'] ?? '',
				$this->name . '-bic' => $data['bic'] ?? ''
			],
			'errors' => $error ? [
				$this->name . '-iban' => $error,
				$this->name . '-bic' => $error
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
		return $renderer->render('@form_configuration/bankaccount.twig', [
			'data' => $this->configuration(),
			'errors' => $errors
		]);
	}

	public function export($value)
	{
		return json_decode($value, true);
	}
}