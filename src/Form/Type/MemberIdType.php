<?php
namespace App\Form\Type;

use App\Validator\Member;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MemberIdType extends AbstractType
{
	public function getParent(): string
	{
		return IntegerType::class;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'constraints' => [
				new NotBlank(),
				new Member(),
			],
		]);
	}
}
