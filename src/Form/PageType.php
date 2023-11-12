<?php
namespace App\Form;

use App\Form\Type\FilemanagerFileType;
use App\Form\Type\CommitteeIdType;
use App\Form\Type\MarkupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Form type for DataModelEditable (aka "Page")
 */
class PageType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('content_en', MarkupType::class, [
				'label' => __('Page Content'),
				'required' => false,
			])
			->add('cover_image_url', FilemanagerFileType::class, [
				'label' => __('Image'),
				'required' => false,
			])
			->add('submit', SubmitType::class);

		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			$iter = $event->getData();
			$form = $event->getForm();

			if ($this->canSetTitel($iter))
				$form->add('titel', TextType::class, [
					'label' => __('Identifier'),
					'constraints' => new Assert\NotBlank(),
					'help' => __('This value is often used in the code base to refer to a specific page.'),
				]);

			if ($this->canSetCommitteeId($iter)) {
				// No additional validation is needed, getChoices makes sure we
				// can only pick options we're allowed to pick.
				$form->add('committee_id', CommitteeIdType::class, [
					'label' => __('Owner'),
				]);

				$form->add('slug', TextType::class, [
					'label' => __('Slug'),
					'help' => __('This sets a custom URL for the page in the format of svcover.nl/<slug>. The slug must be unique (different from any other page)'),
					'constraints' => new Assert\Regex([
						'pattern' => '/^[a-z0-9_-]+$/',
						'message' => __('Slug can only contain lower case letters, numbers, hyphens, and underscores.'),
					]),
					'attr' => [
						'pattern' => '[a-z0-9_-]+',
					],
					'required' => false,
				]);
			}
		});
	}

	public static function canSetTitel(\DataIter $iter)
	{
		return !$iter->has_id() || \get_identity()->member_in_committee(COMMISSIE_EASY);
	}

	public static function canSetCommitteeId(\DataIter $iter)
	{
		return !$iter->has_id()
			|| \get_identity()->member_in_committee(COMMISSIE_BESTUUR)
			|| \get_identity()->member_in_committee(COMMISSIE_KANDIBESTUUR)
			|| \get_identity()->member_in_committee(COMMISSIE_EASY);
	}
}
