<?php
namespace App\Form;

use App\Form\CommitteeMemberType;
use App\Form\DataTransformer\StringToDateTimeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CommitteeType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('type', ChoiceType::class, [
				'label' => __('Type'),
				'choices'  => [
					__('Committee') => \DataModelCommissie::TYPE_COMMITTEE,
					__('Working Group') => \DataModelCommissie::TYPE_WORKING_GROUP,
					__('Other') => \DataModelCommissie::TYPE_OTHER,
				],
				'expanded' => true,
				'attr' => [
					'class' => 'chips',
				],
			])
			->add('naam', TextType::class, [
				'label' => __('Name'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('members', CollectionType::class, [
				'label' => __('Members'),
				'entry_type' => CommitteeMemberType::class,
				'allow_add' => true,
				'allow_delete' => true,
				'delete_empty' =>  function ($value = null) {
					return !count(array_filter($value, function ($value) { return !empty($value); }));
				},
				'entry_options'  => [
					'label' => __('Member'),
				],
				'required' => false,
				'mapped' => false,
			])
			->add('vacancies', DateType::class, [
				'label' => __('Looking for new members until'),
				'required' => false,
				'widget' => 'single_text',
				'help' => __("A banner with the face of the Commissioner of Internal Affairs is shown on the pages of groups that have vacancies. If there's no specific deadline, enter a value more than a year into the future. If the committee or working group is not looking for new members, leave the field empty."),
			])
			->add('submit', SubmitType::class)
		;


		// 'van' and 'tot' are strings in dataiter, with timezone info
		$builder->get('vacancies')->addModelTransformer(new StringToDateTimeTransformer(null, null, 'Y-m-d'));
	}
}
