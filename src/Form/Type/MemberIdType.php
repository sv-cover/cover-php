<?php
namespace App\Form\Type;

use App\Validator\Member;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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
			'label' => __('Member'),
			'constraints' => [
				new NotBlank(),
				new Member(),
			],
		]);
	}

	public function buildView(FormView $view, FormInterface $form, array $options): void
	{
		parent::buildView($view, $form, $options);
		if (!empty($view->vars['value'])) {
			try {
				// TODO: Maybe change to a MemberType, so value will be member with no need for extra query?
				$view->vars['member'] = get_model('DataModelMember')->get_iter($view->vars['value']);
			} catch (\DataIterNotFoundException $e) {
				$view->vars['member'] = null;
			}
		} else {
			$view->vars['member'] = null;
		}
	}
}
