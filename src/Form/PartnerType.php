<?php
namespace App\Form;

use App\Form\DataTransformer\IntToBooleanTransformer;
use App\Form\Type\FilemanagerFileType;
use App\Form\Type\MarkupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;


class PartnerType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('name', TextType::class, [
				'label' => __('Name'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('type', ChoiceType::class, [
				'label' => __('Type'),
				'choices'  => [
					__('Sponsor') => \DataModelPartner::TYPE_SPONSOR,
					__('Main sponsor') => \DataModelPartner::TYPE_MAIN_SPONSOR,
					__('Other') => \DataModelPartner::TYPE_OTHER,
				],
			])
			->add('url', UrlType::class, [
				'label' => __('URL'),
				'default_protocol' => null, // if not, it renders as text type…
				'constraints' => new Assert\Url(),
			])
			->add('logo_url', FilemanagerFileType::class, [
				'label' => __('Logo'),
			])
			->add('logo_dark_url', FilemanagerFileType::class, [
				'label' => __('Logo (dark mode)'),
				'required' => false,
				'help' => __('Dark mode version of the logo, defaults to the normal logo if not provided'),
			])
			->add('profile', MarkupType::class, [
				'label' => __('Profile'),
				'required' => false,
			])
			->add('has_banner_visible', CheckboxType::class, [
				'label'    => __('Show banner in website footer'),
				'required' => false,
			])
			->add('has_profile_visible', CheckboxType::class, [
				'label'    => __('Show profile on carreer page'),
				'required' => false,
			])
			->add('submit', SubmitType::class)
		;

		// Booleans are stored as int in the database
		$builder->get('has_banner_visible')->addModelTransformer(new IntToBooleanTransformer());
		$builder->get('has_profile_visible')->addModelTransformer(new IntToBooleanTransformer());
	}
}
