<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Form type for DataModelEditable (aka "Page")
 */
class PageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content_en', MarkupType::class, [
                'label' => __('Page Content'),
                'required' => false,
            ])
            ->add('cover_image_url', FilemanagerFileType::class, [
                'label' => __('Image'),
                'required' => false,
            ])
            ->add('submit', SubmitType::class)
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $iter = $event->getData();
            $form = $event->getForm();

            if ($this->canSetTitel($iter))
                $form->add('titel', TextType::class, [
                    'label' => __('Identifier'),
                    'constraints' => new Assert\NotBlank(),
                    'help' => __('This value is often used in the code base to refer to a specific page.'),
                ]);

            if ($this->canSetCommitteeId($iter))
                // No additional validation is needed, getChoices makes sure we
                // can only pick options we're allowed to pick.
                $form->add('committee_id', ChoiceType::class, [
                    'label' => __('Owner'),
                    'choice_loader' => new CallbackChoiceLoader(function() use ($iter) {
                        return \get_model('DataModelCommissie')->get_committee_choices_for_iter($iter);
                    }),
                ]);
        });
    }

    public static function canSetTitel(\DataIter $iter)
    {
        return !$iter->has_id() || \get_identity()->member_in_committee(COMMISSIE_EASY);
    }

    public static function canSetCommitteeId(\DataIter $iter)
    {
        return !$iter->has_id()
            || \get_identity()->member_in_committee(COMMISSIE_BESTUUR)
            || \get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
            || \get_identity()->member_in_committee(COMMISSIE_EASY);
    }
}
