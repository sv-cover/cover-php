<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;


use App\Form\DataTransformer\IntToBooleanTransformer;

/**
 * Form type for DataModelConfiguratie (aka "Settings")
 */
class SettingsType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('key', TextType::class, [
				'label' => __('Key'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(['max' => 100]),
				],
			])
			->add('value', TextareaType::class, [
				'label' => __('Value'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('submit', SubmitType::class)
		;
	}
}
