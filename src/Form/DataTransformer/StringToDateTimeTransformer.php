<?php 
namespace App\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;

class StringToDateTimeTransformer extends DateTimeToStringTransformer 
{
    public function transform($value): ?\DateTime
    {
        return parent::reverseTransform($value);
    }

    public function reverseTransform($value): ?string
    {
        $transformed = parent::transform($value);
        return empty($transformed) ? null : $transformed;
    }
}
