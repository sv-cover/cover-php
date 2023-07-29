<?php
namespace App\Form;

use App\Form\DataTransformer\StringToDateTimeTransformer;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegistrationType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			// Basic info
			->add('first_name', TextType::class, [
				'label' => __('First name'),
				'constraints' => [
					new Assert\NotBlank([
						'message' => '{{ label }} cannot empty.',
					]),
					new Assert\Length([
						'max' => 255,
						'maxMessage' => '{{ label }} cannot be longer than {{ limit }} characters.',
					]),
				],
				'attr' => [
					'placeholder' => __('E.g. John'),
				],
			])
			->add('family_name_preposition', TextType::class, [
				'label' => __('Infix'),
				'required' => false,
				'constraints' => [
					new Assert\Length([
						'max' => 255,
						'maxMessage' => '{{ label }} cannot be longer than {{ limit }} characters.',
					]),
				],
				'attr' => [
					'placeholder' => __('E.g. von'),
				],
				'help' => __('Infix (tussenvoegsel) is a common thing in Dutch surnames. It’s separated from the rest of the surname to sort your surname with N instead of V if your name is John von Neuman. Leave this field empty if you don’t know what to do with this.'),
			])
			->add('family_name', TextType::class, [
				'label' => __('Last name'),
				'constraints' => [
					new Assert\NotBlank([
						'message' => '{{ label }} cannot empty.',
					]),
					new Assert\Length([
						'max' => 255,
						'maxMessage' => '{{ label }} cannot be longer than {{ limit }} characters.',
					]),
				],
				'attr' => [
					'placeholder' => __('E.g. Neumann'),
				],
			])
			->add('birth_date', DateType::class, [
				'label' => __('Birthdate'),
				'widget' => 'single_text',
				'constraints' => [
					/* There's legal reason why people have to be at least 10 years old, but this limit
					was introduced after we noticed people entered the current year. The 10 year limit
					prevents this while still allowing extremely young students to register (which
					happens sometimes) */
					new Assert\Callback(function ($value, ExecutionContextInterface $context, $payload) {
						var_dump($value);
					}),

					new Assert\LessThan([
						'value' => '-10 years',
						'message' => 'You need to be at least 10 years old!',
					]),
				],
			])


			// Contact


			->add('email_address', EmailType::class, [
				'label' => __('Email address'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Email(),
					new Assert\Length(['max' => 255]),
				],
				'help' => __('Please use your personal email, <i>NOT</i> your university email. Then we can still contact you about your membership after your graduation. We’ll send you an email with a confirmation link after completing this form.'),
				'help_html' => true,
			])
			->add('phone_number', TelType::class, [
				'label' => __('Phone number'),
				'constraints' => [
					new Assert\NotBlank(),
					new AssertPhoneNumber(['defaultRegion' => 'NL']),
				]
			])


			// Address


			->add('street_name', TextType::class, [
				'label' => __('Street name + number'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Regex([
						'pattern' => '/\d+/', // Don't validate client side.
						'message' => __('Looks like you forgot your house number!'),
					]),
					new Assert\Length(['max' => 255]),
				],
				'attr' => [
					'placeholder' => __('E.g. Nijenborgh 9'),
				],
			])
			->add('postal_code', TextType::class, [
				'label' => __('Postal code'),
				'constraints' => [
					new Assert\NotBlank([
						'message' => '{{ label }} cannot empty.',
					]),
					new Assert\Regex([
						'pattern' => '/^\d{4}\s*[a-z]{2}$/i', // Don't validate client side.
						'message' => __('That does not look like a postal code! Dutch postal codes are 4 numbers followed by 2 letters.'),
					]),
					new Assert\Length([
						'max' => 7,
						'maxMessage' => '{{ label }} cannot be longer than {{ limit }} characters.',
					]),
				],
				'attr' => [
					'placeholder' => __('E.g. 9747 AG'),
				],
			])
			->add('place', TextType::class, [
				'label' => __('Place of residence'),
				'constraints' => [
					new Assert\NotBlank([
						'message' => '{{ label }} cannot empty.',
					]),
					new Assert\Length([
						'max' => 255,
						'maxMessage' => '{{ label }} cannot be longer than {{ limit }} characters.',
					]),
				],
				'attr' => [
					'placeholder' => __('E.g. Groningen'),
				],
			])


			// Study (these are part of membership in secreatar)


			->add('membership_student_number', TextType::class, [
				'label' => __('Student number'),
				'constraints' => [
					new Assert\Regex([
						'pattern' => '/^[sS]?\d+$/',
						'message' => __('Your student number has to contain a number!'),
					]),
				],
				'attr' => [
					'placeholder' => __('E.g. s123456'),
				],
			])
			->add('membership_study_name', TextType::class, [
				'label' => __('Study'),
				'constraints' => [
					new Assert\NotBlank([
						'message' => '{{ label }} cannot empty.',
					]),
				],
				'attr' => [
					'placeholder' => __('Pick one from the list or type if your study isn’t on it'),
				],
			])
			->add('membership_study_phase', ChoiceType::class, [
				'label' => __('Phase'),
				'choices'  => [
					// TODO: what about premaster?
					__('Bachelor') => 'b',
					__('Master') => 'm',
				],
				'invalid_message' => 'This is not a valid study phase',
			])
			->add('membership_year_of_enrollment', IntegerType::class, [
				'label' => __('Starting year'),
				'constraints' => [
					new Assert\Range([
						'min' => 1900,
						'max' => 2100,
					]),
				],
				'attr' => [
					'placeholder' => __('In which year did you start or are you expecting to start with your study.'),
				],
			])


			// Payment


			->add('iban', TextType::class, [
				'label' => __('IBAN'),
				'constraints' => new Assert\AtLeastOneOf([
					'constraints' => [
						new Assert\Iban(),
						new Assert\EqualTo(get_config_value('no_iban_string')),
					],
					// We don't want a message suggesting the existence of a no_iban_string. Just show it's an invalid IBAN.
					'message' => __('This is not a valid International Bank Account Number (IBAN).'),
					'includeInternalMessages' => false,
				]),
			])
			->add('bic', TextType::class, [
				'label' => __('BIC'),
				'required' => false,
				'constraints' => [
					new Assert\Bic(),
				],
				'help' => __("BIC is only required if your IBAN <i>does not</i> start with ‘NL’"), // This is never validated for better UX. Treasurer can always look it up.
				'help_html' => true,
			])
			->add('sepa_mandate', CheckboxType::class, [
				'label' => __('I hereby authorize Cover to automatically deduct the membership fee, costs for attending activities, and additional costs (e.g. food and drinks) from my bank account for the duration of my membership.'),
				'required' => true,
				'help' => __('<p>By checking this box, you authorise (A) study association Cover (ID: NL48ZZZ400267070000) to send instructions to your bank to debit your account and (B) your bank to debit your account in accordance with the instructions from the Creditor. As part of your rights, you are entitled to a refund from your bank under the terms and conditions of your agreement with your bank. A refund must be claimed within 8 weeks starting from the date on which your account was debited. Your rights are explained in a statement that you can obtain from your bank.</p><p>If you update your personal details or account number in your Cover account, these changes will be reflected in the mandate.</p>'),
				'help_html' => true,
			])


			// Other


			->add('terms_conditions_agree', CheckboxType::class, [
				'label' => __('I agree to the terms and conditions.'),
				'required' => true,
			])
			->add('spam', TextType::class, [
				'label' => __('What is the colour of grass? (I am not a robot)'),
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Choice(['groen', 'green', 'coverrood', 'cover red']),
				],
				'help' => __("If you’re not sure, just enter ‘green’.")
			])
			->add('submit', SubmitType::class, ['label' => __('Become a member')])
		;

		// Define some transformers to ensure cleaner input
		$builder->get('postal_code')->addViewTransformer(new CallbackTransformer('strtoupper', 'strtoupper'));
		$builder->get('membership_student_number')->addViewTransformer(new CallbackTransformer(fn($v) => $v, fn($v) => ltrim($v, 'sS')));
		$builder->get('iban')->addViewTransformer(new CallbackTransformer('strtoupper', fn($v) => str_replace(' ', '', strtoupper($v))));
		$builder->get('bic')->addViewTransformer(new CallbackTransformer('strtoupper', fn($v) => str_replace(' ', '', strtoupper($v))));
		$builder->get('spam')->addViewTransformer(new CallbackTransformer('strtolower', 'strtolower'));

		/* Transformers are run BEFORE constraints, rather annoyingly. This means we can't have any constraints that
		expect dates and also use a tranformer to convert everything to strings (because our "ORM" wants it). This
		transformer makes sure the form always has a datetime to work with, but the conversion to string needs to happen
		in the controller.
		TODO: Remove once we've got a better ORM.
		*/
		$builder->get('birth_date')->addModelTransformer(new CallbackTransformer(
			function($value) {
				if (is_string($value))
					return \DateTime::createFromFormat('Y-m-d', $value);
				return $value;
			},
			fn($v) => $v
		));
	}
}
