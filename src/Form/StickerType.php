<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;


use App\Form\DataTransformer\IntToBooleanTransformer;

/**
 * Form type for DataModelConfiguratie (aka "Settings")
 */
class StickerType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('label', TextType::class, [
				'label' => __('Where did you stick it?'),
				'constraints' => new Assert\NotBlank(),
				'attr' => [
					'placeholder' => __('E.g. on a lamppost'),
				]
			])
			->add('omschrijving', TextareaType::class, [
				'label' => __('Description'),
				'required' => false,
				'empty_data' => '', // omschrijving has NOT NULL constraint in DB
			])
			->add('lat', NumberType::class, [
				'label' => __('Latitude'),
				'html5' => true,
				'attr' => ['step' => 0.00001],
			])
			->add('lng', NumberType::class, [
				'label' => __('Longitude'),
				'html5' => true,
				'attr' => ['step' => 0.00001],
			])
			->add('submit', SubmitType::class)
		;

		// Database borks on doubles and I don't want to figure out why
		$builder->get('lat')->addModelTransformer(new CallbackTransformer('floatval', 'strval'));
		$builder->get('lng')->addModelTransformer(new CallbackTransformer('floatval', 'strval'));
	}
}
