<?php
namespace App\Form\Type;

use App\Validator\FilemanagerFile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class FilemanagerType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Length(['max' => 255]),
                new FilemanagerFile(),
            ]
        ]);
    }
}
