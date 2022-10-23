<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;


class Choice implements \SignUpFieldType
{
	public $description;

	public $options;

	public $required;

	public $allow_multiple;

	private $_form;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->required = $configuration['required'] ?? false;

		$this->allow_multiple = $configuration['allow_multiple'] ?? false;

		$this->description = $configuration['description'] ?? '';

		$this->options = $configuration['options'] ?? [];
	}

	public function configuration()
	{
		return [
			'required' => $this->required,
			'allow_multiple' => $this->allow_multiple,
			'description' => $this->description,
			'options' => array_values($this->options)
		];
	}

	public function process(array $post_data, &$error)
	{
		$options = $post_data[$this->name] ?? [];

		if (!is_array($options))
			$options = [$options];

		if (array_diff($options, $this->options) != []) {
			$error = 'Unknown option';
			return false;
		}

		if ($this->required && count($options) === 0) {
			$error = 'Required';
			return false;
		}

		if (!$this->allow_multiple && count($options) > 1) {
			$error = 'You can only pick a single option';
			return false;
		}

		return json_encode($options);
	}

	public function suggest(\DataIterMember $member)
	{
		return null;
	}

	public function render($renderer, $value, $error)
	{
		return $renderer->render('@form_fields/choice.twig', [
			'name' => $this->name,
			'data' => [$this->name => (array) json_decode($value, true)],
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
					'label' => __('Label above the options'),
					'constraints' => new Assert\NotBlank(),
					'required' => false,
				])
				->add('options', CollectionType::class, [
					'label' => __('Options'),
					'entry_type' => TextType::class,
					'allow_add' => true,
					'allow_delete' => true,
					'delete_empty' =>  function ($value = null) {
						return empty($value);
					},
				])
				->add('allow_multiple', CheckboxType::class, [
					'label' => __('Allow multiple options to be picked.'),
					'required' => false,
				])
				->add('required', CheckboxType::class, [
					'label' => __('Picking an option is mandatory.'),
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
		$this->options = $form->get('options')->getData();
		$this->allow_multiple = $form->get('allow_multiple')->getData();
		return true;
	}

	public function render_configuration($renderer, array $form_attr)
	{
		$form = $this->get_configuration_form();
		return $renderer->render('@form_configuration/choice.twig', [
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
		$options = (array) json_decode($value, true);
		return [$this->name => implode('; ', $options)];
	}
}