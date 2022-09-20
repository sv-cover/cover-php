<?php
namespace App\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;


class VacancyType extends AbstractType implements EventSubscriberInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => new NotBlank(),
            ])
            ->add('partner_id', IntegerType::class, [
                'label' => 'Company',
                'required' => false,
            ])
            ->add('partner_name', TextType::class, [
                'required' => false
            ])
            ->add('type', ChoiceType::class, [
                'choices'  => [
                    __('Full-time')          => \DataModelVacancy::TYPE_FULL_TIME,
                    __('Part-time')          => \DataModelVacancy::TYPE_PART_TIME,
                    __('Internship')         => \DataModelVacancy::TYPE_INTERNSHIP,
                    __('Graduation project') => \DataModelVacancy::TYPE_GRADUATION_PROJECT,
                    __('Other/unknown')      => \DataModelVacancy::TYPE_OTHER,
                ],
            ])
            ->add('study_phase', ChoiceType::class, [
                'choices'  => [
                    __('Bachelor Student')   => \DataModelVacancy::STUDY_PHASE_BSC,
                    __('Master Student')     => \DataModelVacancy::STUDY_PHASE_MSC,
                    __('Graduated Bachelor') => \DataModelVacancy::STUDY_PHASE_BSC_GRADUATED,
                    __('Graduated Master')   => \DataModelVacancy::STUDY_PHASE_MSC_GRADUATED,
                    __('Other/unknown')      => \DataModelVacancy::STUDY_PHASE_OTHER,
                ],
            ])
            ->add('url', UrlType::class, [
                'required' => false,
                'default_protocol' => null, // if not, it renders as text type…
                'constraints' => new Url(),
            ])
            ->add('description', MarkupType::class)
            ->add('save', SubmitType::class)
        ;

        // Telling the form builder about the event subscriber used to validate the partner xor requirement
        $builder->addEventSubscriber($this);
    } 

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'validatePartner',
        ];
    }
 
    public function validatePartner(FormEvent $event)
    {
        $submittedData = $event->getData();
 
        if (!(empty($submittedData['partner_id']) xor empty($submittedData['partner_name']))) {
            // This will be a global error message on the form, not on any specific field
            throw new TransformationFailedException(
                'either partner_id or partner_name must be set',
                0, // code
                null, // previous
                __('Either Company or Partner name must be set, but not both.'), // user message
            );
        }
    }
}
