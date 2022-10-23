<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class BankAccount implements \SignUpFieldType
{
	public $name;
	
	public $required;

	private $_form;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;

		$this->autofill = $configuration['autofill'] ?? true;
	}

	public function configuration()
	{
		return [
			'required' => (bool) $this->required,
			'autofill' => (bool) $this->autofill
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
		if (!$this->autofill)
			return null;

		try {
			require_once 'src/services/incassomatic.php';

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

	public function get_configuration_form()
	{
		if (!isset($this->_form))
			$this->_form = \get_form_factory()
				->createNamedBuilder(sprintf('form-field-%s', $this->name), FormType::class, $this->configuration())
				->add('required', CheckboxType::class, [
					'label' => __('Filling in bank account (IBAN) is mandatory.'),
					'required' => false,
				])
				->add('autofill', CheckboxType::class, [
					'label' => __('Autofill this field with member data.'),
					'required' => false,
					'help' => __('Disable if people are not supposed to fill in their own information.'),
				])
				->add('submit', SubmitType::class, [
					'label' => __('Modify field'),
				])
				->getForm();
		return $this->_form;
	}

	public function process_configuration($form)
	{
		$this->required = $form->get('required')->getData();
		$this->autofill = $form->get('autofill')->getData();
		return true;
	}

	public function render_configuration($renderer, array $form_attr)
	{
		$form = $this->get_configuration_form();
		return $renderer->render('@form_configuration/field.twig', [
			'form' => $form->createView(),
			'form_attr' => $form_attr,
		]);
	}

	public function column_labels()
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