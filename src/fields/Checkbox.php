<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class Checkbox implements \SignUpFieldType
{
	public $description;

	public $required;

	private $_form;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;

		$this->description = $configuration['description'] ?? '';
	}

	public function configuration()
	{
		return [
			'required' => $this->required,
			'description' => $this->description
		];
	}

	public function process(array $post_data, &$error)
	{
		$checked = isset($post_data[$this->name]) && $post_data[$this->name] == 'yes';

		if ($this->required && !$checked)
			$error = 'Required';

		return $checked ? '1' : '0';
	}

	public function suggest(\DataIterMember $member)
	{
		return null;
	}

	public function render($renderer, $value, $error)
	{
		return $renderer->render('@form_fields/checkbox.twig', [
			'name' => $this->name,
			'data' => [$this->name => (bool) $value],
			'configuration' => $this->configuration(),
			'errors' => $error ? [$this->name => $error] : []
		]);
	}

	public function get_configuration_form()
	{
		if (!isset($this->_form))
			$this->_form = \get_form_factory()
				->createNamedBuilder(sprintf('form-field-%s', $this->name), FormType::class, $this->configuration())
				->add('description', TextType::class, [
					'label' => __('Text next to the checkbox'),
					'constraints' => new Assert\NotBlank(),
				])
				->add('required', CheckboxType::class, [
					'label' => __('Checking this checkbox is mandatory.'),
					'required' => false,
				])
				->add('submit', SubmitType::class, [
					'label' => __('Modify field'),
				])
				->getForm();
		return $this->_form;
	}

	public function process_configuration($form)
	{
		$this->description = $form->get('description')->getData();
		$this->required = $form->get('required')->getData();
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
		return [$this->name => $this->description];
	}

	public function export($value)
	{
		return [$this->name => $value ? 1 : 0];
	}
}