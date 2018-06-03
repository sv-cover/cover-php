<?php

namespace fields;

class Editable implements \SignUpFieldType
{
	public $name;

	public $content;

	public function __construct($name, array $configuration)
	{
		$this->name = $name;

		$this->content = $configuration['content'] ?? '';
	}

	public function configuration()
	{
		return [
			'content' => $this->content
		];
	}

	public function process(array $post_data, &$error)
	{
		return null;
	}

	public function render($renderer, $value, $error)
	{
		return $renderer->render('@form_fields/editable.twig', [
			'name' => $this->name,
			'configuration' => $this->configuration()
		]);
	}

	public function process_configuration(array $post_data, \ErrorSet $errors)
	{
		$this->content = strval($post_data['content']);
		return true;
	}

	public function render_configuration($renderer, \ErrorSet $errors)
	{
		return $renderer->render('@form_configuration/editable.twig', [
			'name' => $this->name,
			'data' => $this->configuration()
		]);
	}

	public function export($value)
	{
		return [];
	}
}