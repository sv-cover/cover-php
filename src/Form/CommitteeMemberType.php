<?php
namespace App\Form;

use App\Form\Type\MemberIdType;
use App\Validator\Member;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;


class CommitteeMemberType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('member_id', MemberIdType::class, [
				'label' => __('Member'),
				'constraints' => [
					new Member(),
				],
			])
			->add('functie', TextType::class, [
				'label' => __('Function'),
			])
		;
	}
}
