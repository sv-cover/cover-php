<?php

namespace fields;

class Choice implements \SignUpFieldType
{
	public $description;

	public $options;

	public $required;

	public $allow_multiple;

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

	public function process_configuration(array $post_data, \ErrorSet $errors)
	{
		$this->description = strval($post_data['description']);
		$this->required = !empty($post_data['required']);
		$this->options = array_filter((array) $post_data['options']);
		$this->allow_multiple = !empty($post_data['allow_multiple']);
		return true;
	}

	public function render_configuration($renderer, \ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/choice.twig', [
			'name' => $this->name,
			'data' => $this->configuration(),
			'errors' => $errors
		]);
	}

	public function info()
	{
		return [$this->name => $this->description];
	}

	public function export($value)
	{
		$options = (array) json_decode($value, true);
		return [$this->name => implode('; ', $options)];
	}
}