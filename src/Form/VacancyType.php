<?php
namespace App\Form;

use App\Form\Type\MarkupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class VacancyType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('title', TextType::class, [
				'label' => __('Title'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('partner_id', IntegerType::class, [
				'label' => __('Company'),
				'required' => false,
			])
			->add('partner_name', TextType::class, [
				'required' => false
			])
			->add('type', ChoiceType::class, [
				'label' => __('Type'),
				'choices'  => [
					__('Full-time')          => \DataModelVacancy::TYPE_FULL_TIME,
					__('Part-time')          => \DataModelVacancy::TYPE_PART_TIME,
					__('Internship')         => \DataModelVacancy::TYPE_INTERNSHIP,
					__('Graduation project') => \DataModelVacancy::TYPE_GRADUATION_PROJECT,
					__('Other/unknown')      => \DataModelVacancy::TYPE_OTHER,
				],
			])
			->add('study_phase', ChoiceType::class, [
				'label' => __('Study phase'),
				'choices'  => [
					__('Bachelor Student')   => \DataModelVacancy::STUDY_PHASE_BSC,
					__('Master Student')     => \DataModelVacancy::STUDY_PHASE_MSC,
					__('Graduated Bachelor') => \DataModelVacancy::STUDY_PHASE_BSC_GRADUATED,
					__('Graduated Master')   => \DataModelVacancy::STUDY_PHASE_MSC_GRADUATED,
					__('Other/unknown')      => \DataModelVacancy::STUDY_PHASE_OTHER,
				],
			])
			->add('url', UrlType::class, [
				'label' => __('URL'),
				'required' => false,
				'default_protocol' => null, // if not, it renders as text type…
				'constraints' => new Assert\Url(),
			])
			->add('description', MarkupType::class, [
				'label' => __('Description'),
			])
			->add('submit', SubmitType::class)
		;

		// Telling the form builder about the event subscriber used to validate the partner xor requirement
		// $builder->addEventSubscriber($this);
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

	public function validate_iter(\DataIterVacancy $iter, ExecutionContextInterface $context): void
	{
		if (!(empty($iter['partner_id']) xor empty($iter['partner_name']))) {
			$context->buildViolation(__('Either Company or Partner name must be set, but not both.'))
				->atPath('partner_name') // partner_id is hidden
				->addViolation();
		}
	}
}
