<?php

namespace fields;

class Email implements \SignUpFieldType
{
	public $name;
	
	public $label;

	public $required;

	public $multiline;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->label = $configuration['label'] ?? 'Email';

		$this->required = $configuration['required'] ?? false;
	}

	public function configuration()
	{
		return [
			'label' => $this->label,
			'required' => (bool) $this->required
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

	public function process_configuration(array $post_data, \ErrorSet $errors)
	{
		$this->label = strval($post_data['label']);
		$this->required = !empty($post_data['required']);
		return true;
	}

	public function render_configuration($renderer, \ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/email.twig', [
			'data' => $this->configuration(),
			'errors' => $errors
		]);
	}

	public function export($value)
	{
		return [$this->label => $value];
	}
}