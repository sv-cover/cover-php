<?php

namespace fields;

use App\Form\Type\MarkupType;
use App\Form\Type\PresentationType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;

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

	public function process(Form $form)
	{
		return null;
	}

	public function prefill(\DataIterMember $member)
	{
		return null;
	}

	public function build_form(FormBuilderInterface $builder)
	{
		$builder
			->add($this->name, PresentationType::class, [
				'content' => $this->content,
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

	public function process_configuration(Form $form)
	{
		$this->content = $form->get('content')->getData();
		return true;
	}

	public function render_configuration($renderer, array $form_attr)
	{
		$form = $this->get_configuration_form();
		return $renderer->render('@theme/signup/configuration/field.twig', [
			'form' => $form->createView(),
			'form_attr' => $form_attr,
		]);
	}

	public function column_labels()
	{
		return [];
	}

	public function get_form_data($value)
	{
		return $this->export($value);
	}

	public function export($value)
	{
		return [];
	}
}