<?php

namespace fields;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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

	public function process(array $post_data, &$error)
	{
		$address = trim($post_data[$this->name . '-address'] ?? '');

		$city = trim($post_data[$this->name . '-city'] ?? '');

		if ($this->required && ($address == '' || $city == ''))
			$error = __('Value required');

		return json_encode(compact('address', 'city'));
	}

	public function suggest(\DataIterMember $member)
	{
		if (!$this->autofill)
			return null;

		return json_encode([
			'address' => $member['adres'],
			'city' => $member['woonplaats']
		]);
	}

	public function render($renderer, $value, $error)
	{
		if ($value !== null)
			$data = json_decode($value, true);
		else
			$data = [];

		return $renderer->render('@form_fields/address.twig', [
			'name' => $this->name,
			'configuration' => $this->configuration(),
			'data' => [
				$this->name . '-address' => $data['address'] ?? '',
				$this->name . '-city' => $data['city'] ?? ''
			],
			'errors' => $error ? [
				$this->name . '-address' => $error,
				$this->name . '-city' => $error
			] : []
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
			$this->name . '-address' => 'address',
			$this->name . '-city' => 'place of residence'
		];
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