<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Phone implements \SignUpFieldType
{
	public $name;
	
	public $label;

	public $required;

	private $_form;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->label = $configuration['label'] ?? 'Phone';

		$this->required = $configuration['required'] ?? false;

		$this->autofill = $configuration['autofill'] ?? true;
	}

	public function configuration()
	{
		return [
			'label' => $this->label,
			'required' => (bool) $this->required,
			'autofill' => (bool) $this->autofill
		];
	}

	public function process(Form $form)
	{
		$value = $form->get($this->name)->getData();
		// A phone number doesn't need to contain spaces
		$value = str_replace(' ', '', $value);
		return $value;
	}

	public function prefill(\DataIterMember $member)
	{
		if (!$this->autofill)
			return null;

		return $member['telefoonnummer'];
	}

	public function build_form(FormBuilderInterface $builder)
	{
		$builder
			->add($this->name, TelType::class, [
				'label' => __('Phone number'),
				'required' => $this->required,
				'constraints' => $this->required ? new Assert\NotBlank() : [],
				'attr' => [
					'placeholder' => __('E.g. +31 6 12345678'),
				],
			]);
	}

	public function get_configuration_form()
	{
		if (!isset($this->_form))
			$this->_form = \get_form_factory()
				->createNamedBuilder(sprintf('form-field-%s', $this->name), FormType::class, $this->configuration())
				->add('required', CheckboxType::class, [
					'label' => __('Filling in phone number is mandatory.'),
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
		return [$this->name => $this->label];
	}

	public function get_form_data($value)
	{
		return $this->export($value);
	}

	public function export($value)
	{
		return [$this->name => $value];
	}
}