<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Address implements \SignUpFieldType
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
		return json_encode([
			'address' => $form->get($this->name . '-address')->getData(),
			'city' => $form->get($this->name . '-city')->getData(),
		]);
	}

	public function prefill(\DataIterMember $member)
	{
		if (!$this->autofill)
			return null;

		return json_encode([
			'address' => $member['adres'],
			'city' => $member['woonplaats']
		]);
	}

	public function build_form(FormBuilderInterface $builder)
	{
		$builder
			->add($this->name . '-address', TextType::class, [
				'label' => __('Address'),
				'required' => $this->required,
				'constraints' => $this->required ? new Assert\NotBlank() : [],
				'attr' => [
					'placeholder' => __('E.g. Nijenborgh 9'),
				],
				'help' => __('Street name + number'),
			])
			->add($this->name . '-city', TextType::class, [
				'label' => __('Place of residence'),
				'required' => $this->required,
				'constraints' => $this->required ? new Assert\NotBlank() : [],
				'attr' => [
					'placeholder' => __('E.g. Groningen'),
				],
			]);
	}

	public function get_configuration_form()
	{
		if (!isset($this->_form))
			$this->_form = \get_form_factory()
				->createNamedBuilder(sprintf('form-field-%s', $this->name), FormType::class, $this->configuration())
				->add('required', CheckboxType::class, [
					'label' => __('Filling in address is mandatory.'),
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
			$this->name . '-address' => 'address',
			$this->name . '-city' => 'place of residence'
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
			$this->name . '-address' => $data['address'] ?? '',
			$this->name . '-city' => $data['city'] ?? ''
		];
	}
}