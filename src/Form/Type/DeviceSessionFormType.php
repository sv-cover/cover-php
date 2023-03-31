<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;


class DeviceSessionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('device_name', TextType::class, [
                'label' => __('Device name'),
                'constraints' => new Assert\NotBlank(),
            ])
            ->add('device_enabled', CheckboxType::class, [
                'label'    => __('Device enabled'),
                'required' => false,
            ])
            ->add('submit', SubmitType::class)
        ;
    }
}
