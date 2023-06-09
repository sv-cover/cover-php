<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;


class MailinglistType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('adres', EmailType::class, [
				'label' => __('List email address'),
				'constraints' => [new Assert\NotBlank(), new Assert\Email()],
				'attr' => [
					'placeholder' => __('e.g. listname@svcover.nl'),
				]
			])
			->add('type', ChoiceType::class, [
				'label' => __('Type'),
				'choices'  => [
					__('Opt-in') => \DataModelMailinglist::TYPE_OPT_IN,
					__('Opt-out') => \DataModelMailinglist::TYPE_OPT_OUT,
				],
				'help' => __('What type of list is this?'),
			])
			->add('toegang', ChoiceType::class, [
				'label' => __('Access'),
				'choices'  => [
					__('Everyone') => \DataModelMailinglist::TOEGANG_IEDEREEN,
					__('Only people subscribed to this list (and the list owner)') => \DataModelMailinglist::TOEGANG_DEELNEMERS,
					__('Only *@svcover.nl addresses') => \DataModelMailinglist::TOEGANG_COVER,
					__('Only the committee that owns this list') => \DataModelMailinglist::TOEGANG_EIGENAAR,
					__('People subscribed to this list and *@svcover.nl addresses') => \DataModelMailinglist::TOEGANG_COVER_DEELNEMERS,
				],
				'help' => __('Who can send emails to this list?'),
			])
			->add('naam', TextType::class, [
				'label' => __('Name'),
				'constraints' => new Assert\NotBlank(),
			])
			->add('omschrijving', TextareaType::class, [
				'label' => __('Description'),
			])
			->add('tag', TextType::class, [
				'label' => __('Tag'),
				'constraints' => new Assert\NotBlank(),
				'help' => __('Puts \'[<tag>]\' before the email subject, leave blank for none.')
			])
			->add('publiek', CheckboxType::class, [
				'label' => __('People can subscribe themselves to this list.'),
				'required' => false,
				'help' => __('This makes the list show up in the mailing list tab of the profile page.'),
			])
			->add('has_members', CheckboxType::class, [
				'label' => __('Contains members.'),
				'required' => false,
				'help' => __('Opt-in: members can opt-in for this list. Opt-out: all members are subscribed by default.'),
			])
			->add('has_contributors', CheckboxType::class, [
				'label' => __('Contains contributors.'),
				'required' => false,
				'help' => __('Opt-in: contributors can opt-in for this list. Opt-out: all contributors are subscribed by default.'),
			])
			->add('has_starting_year', IntegerType::class, [
				'label' => __('Starting year'),
				'required' => false,
				'help' => __('Opt-in: only people from this year can opt-in. Opt-out: everybody from this year is subscribed by default. Leave blank for none.')
			])
			->add('submit', SubmitType::class)
		;

		$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			$iter = $event->getData();
			$form = $event->getForm();

			// No additional validation is needed, getCommitteeChoices makes sure we
			// can only pick options we're allowed to pick.
			$form->add('commissie', ChoiceType::class, [
				'label' => __('Owner'),
				'choice_loader' => new CallbackChoiceLoader(function() use ($iter) {
					return \get_model('DataModelCommissie')->get_committee_choices_for_iter($iter, 'commissie');
				}),
				'help' => __('Which committee may subscribe and unsubscribe people to this list?'),
			]);
		});

		// Ensure list address is always lowercase. We only need to reverse-transform, but it doesn't hurt to do it both ways…
		$builder->get('adres')->addModelTransformer(new CallbackTransformer('strtolower', 'strtolower'));
	}
}
