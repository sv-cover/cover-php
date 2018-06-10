<?php

namespace fields;

class Checkbox implements \SignUpFieldType
{
	public $description;

	public $required;

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

	public function process_configuration(array $post_data, \ErrorSet $errors)
	{
		$this->description = strval($post_data['description']);
		$this->required = !empty($post_data['required']);
		return true;
	}

	public function render_configuration($renderer, \ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/checkbox.twig', [
			'data' => $this->configuration(),
			'errors' => $errors
		]);
	}

	public function export($value)
	{
		return [$this->description => $value ? 0 : 1];
	}
}