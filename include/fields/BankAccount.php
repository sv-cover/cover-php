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

		if ($bic != '' && !\IsoCodes\SwiftBic::validate($bic))
			$error = __('Invalid BIC code');

		return json_encode(compact('iban', 'bic'));
	}

	public function suggest(\DataIterMember $member)
	{
		try {
			require_once 'include/incassomatic.php';

			$incasso_api = \incassomatic\shared_instance();
			$contracts = $incasso_api->getContracts($member);

			// Only use valid contracts
			$contract = current(array_filter($contracts, function($contract) { return $contract->is_geldig; }));

			if (!$contract)
				return null;

			return json_encode(['iban' => $contract->iban, 'bic' => $contract->bic]);
		} catch (\RuntimeException $e) {
			return null;
		}
	}

	public function render($renderer, $value, $error)
	{
		$data = $value !== null ? json_decode($value, true) : [];

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
			'name' => $this->name,
			'data' => $this->configuration(),
			'errors' => $errors
		]);
	}

	public function info()
	{
		return [
			$this->name . '-iban' => 'iban',
			$this->name . '-bic' => 'bic'
		];
	}

	public function export($value)
	{
		$data = $value !== null ? json_decode($value, true) : [];
		return [
			$this->name . '-iban' => $data['iban'] ?? '',
			$this->name . '-bic' => $data['bic'] ?? ''
		];
	}
}