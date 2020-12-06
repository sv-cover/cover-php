<?php

namespace fields;

class Phone implements \SignUpFieldType
{
	public $name;
	
	public $label;

	public $required;

	public $multiline;

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

	public function process(array $post_data, &$error)
	{
		$value = trim($post_data[$this->name] ?? '');

		// A phone number doesn't need to contain spaces
		$value = str_replace(' ', '', $value);

		if ($value != '' && !preg_match('/^\+?\d+$/', $value))
			$error = __('Invalid phone number');

		if ($this->required && $value == '')
			$error = __('Value required');

		return $value;
	}

	public function suggest(\DataIterMember $member)
	{
		if (!$this->autofill)
			return null;

		return $member['telefoonnummer'];
	}

	public function render($renderer, $value, $error)
	{
		return $renderer->render('@form_fields/phone.twig', [
			'name' => $this->name,
			'data' => [$this->name => $value],
			'configuration' => $this->configuration(),
			'errors' => $error ? [$this->name => $error] : []
		]);
	}

	public function process_configuration(array $post_data, \ErrorSet $errors)
	{
		$this->label = strval($post_data['label'] ?? $this->label);
		$this->required = !empty($post_data['required']);
		$this->autofill = !empty($post_data['autofill']);
		return true;
	}

	public function render_configuration($renderer, \ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/phone.twig', [
			'name' => $this->name,
			'data' => $this->configuration(),
			'errors' => $errors
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