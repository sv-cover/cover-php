<?php
namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;


/**
 * Extension to enable easy configuration of Bulma colors on buttons.
 */
class BulmaButtonTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ButtonType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Default is empty, button_widget in bulma_layout.html.twig will set appropriate color based on button type
        $resolver->setDefaults([
            'color' => '',
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        $view->vars['color'] = $options['color'];
    }
}
