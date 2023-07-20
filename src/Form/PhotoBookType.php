<?php
namespace App\Form;

use App\Form\DataTransformer\StringToDateTimeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextAreaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PhotoBookType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('titel', TextType::class, [
				'label' => __('Name'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(['max' => 255]),
				],
			])
			->add('date', DateType::class, [
				'label' => __('Date'),
				'widget' => 'single_text',
			])
			->add('fotograaf', TextType::class, [
				'label' => __('Photographer'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(['max' => 255]),
				],
			])
			->add('visibility', ChoiceType::class, [
				'label' => __('Visible to'),
				'choices'  => [
					__('Public') => \DataModelPhotobook::VISIBILITY_PUBLIC,
					__('Only members') => \DataModelPhotobook::VISIBILITY_MEMBERS,
					__('Only committee members') => \DataModelPhotobook::VISIBILITY_ACTIVE_MEMBERS,
					__('Only PhotoCee') => \DataModelPhotobook::VISIBILITY_PHOTOCEE,
				],
				'expanded' => true,
				'attr' => [
					'class' => 'chips',
				],
			])
			->add('beschrijving', TextareaType::class, [
				'label' => __('Description'),
				'required' => false,
			])
			->add('submit', SubmitType::class)
		;


		// 'van' and 'tot' are strings in dataiter, with timezone info
		$builder->get('date')->addModelTransformer(new StringToDateTimeTransformer(null, null, 'Y-m-d'));
	}
}
