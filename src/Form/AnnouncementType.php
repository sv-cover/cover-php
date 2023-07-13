<?php
namespace App\Form;

use App\Form\Type\MarkupType;
use App\Form\Type\CommitteeIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;


class AnnouncementType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('visibility', ChoiceType::class, [
				'label' => __('Visible to'),
				'choices'  => [
					__('Everyone') => \DataModelAnnouncement::VISIBILITY_PUBLIC,
					__('Only logged in members') => \DataModelAnnouncement::VISIBILITY_MEMBERS,
					__('Only logged in active members') => \DataModelAnnouncement::VISIBILITY_ACTIVE_MEMBERS,
				],
			])
			->add('subject', TextType::class, [
				'label' => __('Subject'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('message', MarkupType::class, [
				'label' => __('Message'),
			])
			->add('committee_id', CommitteeIdType::class, [
				'label' => __('Post as committee'),
			])
			->add('submit', SubmitType::class)
		;
	}
}
