<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;


use App\Form\DataTransformer\StringToDateTimeTransformer;


class SignUpFormFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('open_on', DateTimeType::class, [
                'label' => __('Open date'),
                'help' => __("People will be able to register from this date. Make sure you don't open registrations before you finished configuring the sign-up form."),
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('closed_on', DateTimeType::class, [
                'label' => __('Deadline'),
                'help' => __("If you do not have a deadline, leave this field blank."),
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('participant_limit', IntegerType::class, [
                'label' => __('Participant limit'),
                'help' => __("If you do not have a participant limit, leave this field blank."),
                'required' => false,
            ])
            ->add('submit', SubmitType::class)
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $iter = $event->getData();
            $form = $event->getForm();

            // No additional validation is needed, getCommitteeChoices makes sure we
            // can only pick options we're allowed to pick.
            $form->add('agenda_id', ChoiceType::class, [
                'label' => __('Event'),
                'choice_loader' => new CallbackChoiceLoader(function() use ($iter) {
                    return $this->getEventChoices($iter);
                }),
                'choice_value' => function ($entity) {
                    return $entity;
                },
                'help' => __('If you link the form to an event, it will be shown next on the event page.'),
                'placeholder' => __('— No event —'),
                'required' => false,
            ]);

            // No additional validation is needed, getCommitteeChoices makes sure we
            // can only pick options we're allowed to pick.
            $form->add('committee_id', ChoiceType::class, [
                'label' => __('Committee'),
                'choice_loader' => new CallbackChoiceLoader(function() use ($iter) {
                    return \get_model('DataModelCommissie')->get_committee_choices_for_iter($iter, 'commissie');
                }),
            ]);
        });

        $builder->get('open_on')->addModelTransformer(new StringToDateTimeTransformer());
        $builder->get('closed_on')->addModelTransformer(new StringToDateTimeTransformer());
    }

    public function getEventChoices($iter)
    {
        $filter = ['van__gt' => new \DateTime()];

        // Only show your own committees if you're not admin
        if (
            !\get_identity()->member_in_committee(COMMISSIE_BESTUUR)
            && !\get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
            && !\get_identity()->member_in_committee(COMMISSIE_EASY)
        )
            $filter['committee_id__in'] = array_map(
                function ($committee) { return $committee->get_id(); },
                \get_identity()->member()->get('committees')
            );

        $events = get_model('DataModelAgenda')->find($filter);

        if (
            $iter
            && !empty($iter['agenda_id'])
            && empty(array_filter($events, function($e) use ($iter) { return $e['id'] == $iter['agenda_id']; }))
        )
            $events = array_merge(
                [$iter['agenda_item']],
                $events,
            );

        $options = [];

        foreach ($events as $event)
            $options[sprintf('(%s) %s', $event['committee__naam'], $event['kop'])] = $event['id'];

        return $options;
    }
}
