<?php

namespace fields;

class Text implements \SignUpFieldType
{
	public $name;
	
	public $label;

	public $required;

	public $multiline;

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

	public function process(array $post_data, &$error)
	{
		$value = trim($post_data[$this->name] ?? '');

		if ($this->required && $value == '')
			$error = __('Value required');

		return $value;
	}

	public function suggest(\DataIterMember $member)
	{
		return null;
	}

	public function render($renderer, $value, $error)
	{
		return $renderer->render('@form_fields/text.twig', [
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
		$this->multiline = !empty($post_data['multiline']);
		return true;
	}

	public function render_configuration($renderer, \ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/text.twig', [
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