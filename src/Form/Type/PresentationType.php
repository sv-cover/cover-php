<?php
namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\BaseType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Render markup in form for presentation.
 * Mainly intended for use in SignUp form system. Question yourself if you think
 * you need it anywhere else.
 * @author Martijn Luinstra <mail@martijnluinstra.nl>
 */
class PresentationType extends BaseType
{
	public function getParent(): ?string
	{
		return null;
	}

	public function getBlockPrefix(): string
	{
		return 'presentation';
	}

	public function configureOptions(OptionsResolver $resolver)
	{
		parent::configureOptions($resolver);

		$resolver->setDefault('auto_initialize', false);
		$resolver->setDefault('content', '');
	}

	public function buildView(FormView $view, FormInterface $form, array $options): void
	{
		parent::buildView($view, $form, $options);
		$view->vars['content'] = $options['content'];
	}
}
