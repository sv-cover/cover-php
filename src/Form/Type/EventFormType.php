<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use App\Form\DataTransformer\IntToBooleanTransformer;
use App\Form\DataTransformer\StringToDateTimeTransformer;


class EventFormType extends AbstractType
{
    private $_iter;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('kop', TextType::class, [
                'label' => __('Title'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 100]),
                ],
                'attr' => ['maxlength' => 100],
                'priority' => 1000, // Should be rendered before "committee_id"
            ])
            ->add('van', DateTimeType::class, [
                'label' => __('Start'),
                'constraints' => new Assert\Callback([
                    'callback' => [$this, 'validate_datetime'],
                    'payload' => ['message' => __("Time travel is not allowed: your event can't start in the past.")]
                ]),
                'widget' => 'single_text',
            ])
            ->add('tot', DateTimeType::class, [
                'label' => __('End'),
                'constraints' => new Assert\Callback([
                    'callback' => [$this, 'validate_datetime'],
                    'payload' => ['message' => __("Time travel is not allowed: your event can't end in the past.")]
                ]),
                'required' => false,
                'widget' => 'single_text',
                'help' => __('Provide and end time to help (potential) attendees plan ahead.'),
            ])
            ->add('locatie', TextType::class, [
                'label' => __('Location'),
                'constraints' => new Assert\Length(['max' => 100]),
                'attr' => ['maxlength' => 100],
                'required' => false,
            ])
            ->add('image_url', FilemanagerFileType::class, [
                'label' => __('Image'),
                'required' => false,
                'help' => __("This image will neatly decorate your event page, and help stand it out on the homepage. Design hint: the image will always be cropped to a 2:1 ratio (with one exception, when it's a 1:1 (square) cropped out of the centre).")
            ])
            ->add('facebook_id', TextType::class, [
                'label' => __('Link to Facebook event'),
                'required' => false,
                'constraints' => new Assert\Callback([$this, 'validate_facebook_id']),
            ])
            ->add('beschrijving', MarkupType::class, [
                'label' => __('Description'),
                'constraints' => new Assert\NotBlank(),
            ])
            ->add('private', CheckboxType::class, [
                'label'    => __('Only visible to members'),
                'required' => false,
            ])
            ->add('extern', CheckboxType::class, [
                'label'    => __('This event is not organised by Cover'),
                'required' => false,
            ])
            ->add('submit', SubmitType::class)
        ;

        // Init committee_id field

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $iter = $event->getData();
            $form = $event->getForm();
            $this->_iter = $iter;

            // No additional validation is needed, getCommitteeChoices makes sure we
            // can only pick options we're allowed to pick.
            $form->add('committee_id', ChoiceType::class, [
                'label' => __('Committee'),
                'choice_loader' => new CallbackChoiceLoader(function() use ($iter) {
                    return \get_model('DataModelCommissie')->get_committee_choices_for_iter($iter, 'commissie');
                }),
                'priority' => 999, // Should be rendered after "kop"
            ]);
        });

        // Transform data

        // 'van' and 'tot' are strings in dataiter, with timezone info
        $builder->get('van')->addModelTransformer(new StringToDateTimeTransformer(null, null, 'Y-m-d H:i:sT'));
        $builder->get('tot')->addModelTransformer(new StringToDateTimeTransformer(null, null, 'Y-m-d H:i:sT'));

        // Booleans are stored as int in the database
        $builder->get('private')->addModelTransformer(new IntToBooleanTransformer());
        $builder->get('extern')->addModelTransformer(new IntToBooleanTransformer());

        // Make sure we store Facebook event ID, but display event url
        $builder->get('facebook_id')->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if (empty($value))
                    return null;
                return sprintf('https://www.facebook.com/events/%s/', $value);
            },
            function ($value) {
                if (empty(trim($value)))
                    return null;

                $result = preg_match('/^https:\/\/www\.facebook\.com\/events\/(\d+)\//', $value, $matches);

                if ($result)
                    return $matches[1];

                return $value;
            },
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Validate iter, so we can validate constraints depending on other fields.
        $resolver->setDefaults([
            'constraints' => [
                new Assert\Callback([$this, 'validate_iter']),
            ],
        ]);
    }

    public function validate_iter(\DataIterAgenda $iter, ExecutionContextInterface $context): void
    {
        if (!empty($iter['tot']) && new \DateTime($iter['van']) > new \DateTime($iter['tot'])) {
            $context->buildViolation(__("Time travel is not allowed: your event can't end before it starts."))
                ->atPath('tot')
                ->addViolation();
        }
    }

    public function validate_facebook_id($value, ExecutionContextInterface $context, $payload) {
        // Ignore empty values
        if (empty($value))
            return;

        // By the time this is executed, the transformer should have extracted the ID from the URL.
        if (strlen($value) > 20 || !ctype_digit($value))
            $context->buildViolation(__('This does not look like a Facebook event ID.'))
                ->addViolation();
    }

    public function validate_datetime($value, ExecutionContextInterface $context, $payload) {
        // Temporary solution, switch to Assert\GreaterThan once our modeldata is DateTime and not string
        // e.g. new Assert\GreaterThan(['value' => 'now', 'message' => 'Date has to be in the future.'])

        // Ignore empty values
        if (empty($value))
            return;

        // Date can't be in the past, but only for new events
        if (!$this->_iter->has_id() && new \DateTime($value) < new \DateTime())
            $context->buildViolation($payload['message'] ?? __('Date has to be in the future.'))
                ->addViolation();
    }
}
