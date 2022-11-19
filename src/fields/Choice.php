<?php

namespace fields;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
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

	public function process(Form $form)
	{
		return json_encode($form->get($this->name)->getData());
	}

	public function prefill(\DataIterMember $member)
	{
		return null;
	}

	public function build_form(FormBuilderInterface $builder)
	{
		$builder
			->add($this->name, ChoiceType::class, [
				'label' => $this->description,
				'required' => $this->required,
				'constraints' => $this->required ? new Assert\NotBlank(['message' => $this->allow_multiple ? __('Pick at least one option.') : __('Pick an option.')]) : [],
				'choices' => array_combine($this->options, $this->options),
				'expanded' => true,
				'placeholder' => false, // Prevent "None" option when !required && !allow_multiple
				'multiple' => $this->allow_multiple
			]);
		$builder->get($this->name)->addModelTransformer(new CallbackTransformer(
			function ($value) {
				if ($this->allow_multiple)
				{
					// ChoiceField expects array if multiple
					if (is_array($value))
						return $value;
					return [$value];
				}
				else
				{
					// ChoiceField does not expect an array if not multiple
					if (!is_array($value))
						return $value;
					if (count($value) == 0)
						return '';
					return $value[0];
				}
            },
            function ($value) {
            	// We always expect an array
				if (is_array($value))
					return $value;
				return [$value];
            },
		));
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

	public function process_configuration(Form $form)
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
		return $renderer->render('@theme/signup/configuration/choice.twig', [
			'form' => $form->createView(),
			'form_attr' => $form_attr,
		]);
	}

	public function column_labels()
	{
		return [$this->name => $this->description];
	}

	public function get_form_data($value)
	{
		return [$this->name => (array) json_decode($value, true)];
	}

	public function export($value)
	{
		$options = (array) json_decode($value, true);
		return [$this->name => implode('; ', $options)];
	}
}