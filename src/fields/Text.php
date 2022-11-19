<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextAreaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Text implements \SignUpFieldType
{
	public $name;
	
	public $label;

	public $required;

	public $multiline;

	private $_form;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->label = $configuration['label'] ?? '';

		$this->required = $configuration['required'] ?? false;

		$this->multiline = $configuration['multiline'] ?? false;
	}

	public function configuration()
	{
		return [
			'label' => $this->label,
			'required' => (bool) $this->required,
			'multiline' => (bool) $this->multiline
		];
	}

	public function process(Form $form)
	{
		return $form->get($this->name)->getData();
	}

	public function prefill(\DataIterMember $member)
	{
		return null;
	}

	public function build_form(FormBuilderInterface $builder)
	{
		if ($this->multiline)
			$builder
				->add($this->name, TextAreaType::class, [
					'label' => $this->label,
					'required' => $this->required,
					'constraints' => $this->required ? new Assert\NotBlank() : [],
				]);
		else
			$builder
				->add($this->name, TextType::class, [
					'label' => $this->label,
					'required' => $this->required,
					'constraints' => $this->required ? new Assert\NotBlank() : [],
				]);
	}
	
	public function get_configuration_form()
	{
		if (!isset($this->_form))
			$this->_form = \get_form_factory()
				->createNamedBuilder(sprintf('form-field-%s', $this->name), FormType::class, $this->configuration())
				->add('label', TextType::class, [
					'label' => __('Label for text field'),
					'constraints' => new Assert\NotBlank(),
				])
				->add('multiline', ChoiceType::class, [
					'label' => false,
					'expanded' => true,
					'multiple' => false,
					'choices' => [
						__('Single line of text') => false,
						__('Multiple lines of text') => true,
					],
				])
				->add('required', CheckboxType::class, [
					'label' => __('Filling in this field is mandatory.'),
					'required' => false,
				])
				->add('submit', SubmitType::class, [
					'label' => __('Modify field'),
				])
				->getForm();
		return $this->_form;
	}

	public function process_configuration(Form $form)
	{
		$this->label = $form->get('label')->getData();
		$this->required = $form->get('required')->getData();
		$this->multiline = $form->get('multiline')->getData();
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