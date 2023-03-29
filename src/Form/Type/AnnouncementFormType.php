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


class AnnouncementFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('visibility', ChoiceType::class, [
                'label' => __('Visible to'),
                'choices'  => [
                    __('Everyone') => \DataModelAnnouncement::VISIBILITY_PUBLIC,
                    __('Only logged in members') => \DataModelAnnouncement::VISIBILITY_MEMBERS,
                    __('Only logged in active members') => \DataModelAnnouncement::VISIBILITY_ACTIVE_MEMBERS,
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => __('Subject'),
                'constraints' => new Assert\NotBlank(),
            ])
            ->add('message', MarkupType::class, [
                'label' => __('Message'),
            ])
            ->add('submit', SubmitType::class)
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $iter = $event->getData();
            $form = $event->getForm();

            // No additional validation is needed, getCommitteeChoices makes sure we
            // can only pick options we're allowed to pick.
            $form->add('committee_id', ChoiceType::class, [
                'label' => __('Post as committee'),
                'choice_loader' => new CallbackChoiceLoader(function() use ($iter) {
                    return \get_model('DataModelCommissie')->get_committee_choices_for_iter($iter);
                }),
            ]);
        });
    }
}
