<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;


use App\Form\DataTransformer\IntToBooleanTransformer;


class BoardType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('naam', TextType::class, [
				'label' => __('Board name'),
				'constraints' => new Assert\NotBlank(['normalizer' => 'trim']),
				'attr' => [
					'placeholder' => __('e.g. Board XXII'),
				]
			])
			->add('login', TextType::class, [
				'label' => __('Sort order name'),
				'constraints' => new Assert\Regex([
					'pattern' => '/^[a-z0-9-_]+$/',
					'message' => 'Sort order can only contain numbers and lower case letters.',
				]),
				'help' => __('This value can only contain numbers and lower case letters. It will be used to determine the order of the boards and will never be displayed.'),
				'attr' => [
					'placeholder' => __('e.g. bestuur22'),
					'pattern' => '[a-zA-Z0-9]+',
				],
			])
			->add('submit', SubmitType::class)
		;
	}
}
