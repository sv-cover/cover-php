<?php
namespace App\Form;

use App\Form\Type\CommitteeIdType;
use App\Form\Type\MarkupType;
use App\Form\DataTransformer\StringToDateTimeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PollType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('committee_id', CommitteeIdType::class, [
				'label' => __('Author'),
				'required' => false,
				'placeholder' => \get_identity()->member()->get_full_name() ?? __('You'),
			])
			->add('question', MarkupType::class, [
				'label' => __('Question'),
				'constraints' => new Assert\NotBlank(),
				'attr' => [
					'rows' => 1,
				],
			])
			->add('options', CollectionType::class, [
				'label' => __('Choices'),
				'entry_type' => TextType::class,
				'allow_add' => true,
				'allow_delete' => true,
				'delete_empty' =>  function ($value = null) {
					return empty($value);
				},
				'mapped' => false,
				'required' => false,
				'constraints' => [
					new Assert\Count([
						'min' => 2,
						'max' => 10,
						'minMessage' => 'A poll must have at least {{ limit }} options.',
						'maxMessage' => 'A poll cannot have more than {{ limit }} options.',
					])
				]
			])
			->add('closed_on', DateTimeType::class, [
				'label' => __('Closes on'),
				'constraints' => new Assert\Callback([
					'callback' => [$this, 'validate_closed_on'],
				]),
				'widget' => 'single_text',
				'required' => false,
				'help' => __('People can vote until this date. If you provide no date, the poll closes as soon as the next poll is created.'),
			])
			->add('submit', SubmitType::class)
		;

		// 'van' and 'tot' are strings in dataiter, with timezone info
		$builder->get('closed_on')->addModelTransformer(new StringToDateTimeTransformer(null, null, 'Y-m-d H:i:sT'));
	}

	public function validate_closed_on($value, ExecutionContextInterface $context, $payload) {
		// Temporary solution, switch to Assert\GreaterThan once our modeldata is DateTime and not string
		// e.g. new Assert\GreaterThan(['value' => 'now', 'message' => 'Date has to be in the future.'])

		// Ignore empty values
		if (empty($value))
			return;

		// Date can't be in the past
		if (new \DateTime($value) < new \DateTime())
			$context->buildViolation(__('Your poll has to close in the future.'))
				->addViolation();

		if (new \DateTime($value) > new \DateTime('+6 months'))
			$context->buildViolation(__('Your poll cannot close more than 6 months from now.'))
				->addViolation();
	}
}
