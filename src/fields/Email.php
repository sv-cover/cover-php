<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class Email implements \SignUpFieldType
{
	public $name;
	
	public $label;

	public $required;

	private $_form;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->label = $configuration['label'] ?? 'Email';

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

	public function process(array $post_data, &$error)
	{
		$value = trim($post_data[$this->name] ?? '');

		if ($value != '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false)
			$error = __('Invalid e-mail address');

		if ($this->required && $value == '')
			$error = __('Value required');

		return $value;
	}

	public function suggest(\DataIterMember $member)
	{
		if (!$this->autofill)
			return null;

		return $member['email'];
	}

	public function render($renderer, $value, $error)
	{
		return $renderer->render('@form_fields/email.twig', [
			'name' => $this->name,
			'data' => [$this->name => $value],
			'configuration' => $this->configuration(),
			'errors' => $error ? [$this->name => $error] : []
		]);
	}

	public function get_configuration_form()
	{
		if (!isset($this->_form))
			$this->_form = \get_form_factory()
				->createNamedBuilder(sprintf('form-field-%s', $this->name), FormType::class, $this->configuration())
				->add('required', CheckboxType::class, [
					'label' => __('Filling in email is mandatory.'),
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
		return [$this->name => $this->label];
	}

	public function export($value)
	{
		return [$this->name => $value];
	}
}