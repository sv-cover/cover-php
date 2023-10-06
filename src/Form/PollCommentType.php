<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextAreaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PollCommentType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('comment', TextareaType::class, [
				'label' => __('Comment'),
				'constraints' => [
					new Assert\NotBlank(),
				],
				'attr' => [
					'placeholder' => __('Type your comment here…'),
				],
			])
			->add('submit', SubmitType::class)
		;
	}
}
