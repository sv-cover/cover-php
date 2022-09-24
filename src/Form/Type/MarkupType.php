<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class MarkupType extends AbstractType
{
    public function getParent(): string
    {
        return TextareaType::class;
    }
}
