<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

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

	public function process(Form $form)
	{
		$iban = $form->get($this->name . '-iban')->getData();

		// Clean IBAN for good measure
		$iban = preg_replace('/[^A-Z0-9]/u', '', strtoupper($iban));
	
		return json_encode([
			'iban' => $iban,
			'bic' => $form->get($this->name . '-bic')->getData(),
		]);
	}

	public function prefill(\DataIterMember $member)
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

	public function build_form(FormBuilderInterface $builder)
	{
		$builder
			->add($this->name . '-iban', TextType::class, [
				'label' => __('IBAN'),
				'required' => $this->required,
				'constraints' => array_filter([
					$this->required ? new Assert\NotBlank() : null,
					new Assert\Iban(),
				]),
			])
			->add($this->name . '-bic', TextType::class, [
				'label' => __('BIC'),
				'required' => false,
				'constraints' => [
					new Assert\Bic(),
				],
				'help' => __("BIC is required if your IBAN does not start with 'NL'"), // This is never validated for better UX. Treasurer can always look it up.
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

	public function process_configuration(Form $form)
	{
		$this->required = $form->get('required')->getData();
		$this->autofill = $form->get('autofill')->getData();
		return true;
	}

	public function render_configuration($renderer, array $form_attr)
	{
		$form = $this->get_configuration_form();
		return $renderer->render('@theme/signup/configuration/field.twig', [
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

	public function get_form_data($value)
	{
		return $this->export($value);
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