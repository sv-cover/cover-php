<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PhotoType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder->add('beschrijving', TextType::class, [
			'label' => __('Title'),
			'required' => false,
			'constraints' => [
				new Assert\Length(['max' => 255]),
			],
		]);

		// Need extra fields when used in add photos view
		if (!empty($options['add_photo'])) {
			$builder
				->add('add', CheckboxType::class, [
					'label' => __('Add photo to album'),
					'required' => false,
				])
				->add('filepath', HiddenType::class, [
					'required' => true,
				])
			;
		} else {
			$builder->add('submit', SubmitType::class);
		}
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'add_photo' => false,
		]);
	}
}
