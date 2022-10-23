<?php

namespace fields;

use App\Form\Type\MarkupType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class Editable implements \SignUpFieldType
{
	public $name;

	public $content;

	private $_form;

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

	public function suggest(\DataIterMember $member)
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

	public function get_configuration_form()
	{
		if (!isset($this->_form))
			$this->_form = \get_form_factory()
				->createNamedBuilder(sprintf('form-field-%s', $this->name), FormType::class, $this->configuration())
				->add('content', MarkupType::class, [
					'label' => __('Content'),
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
		$this->content = $form->get('content')->getData();
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
		return [];
	}

	public function export($value)
	{
		return [];
	}
}