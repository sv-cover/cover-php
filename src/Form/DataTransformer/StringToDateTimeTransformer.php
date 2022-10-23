<?php 
namespace App\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;

class StringToDateTimeTransformer extends DateTimeToStringTransformer 
{
    public function transform($value): ?\DateTime
    {
        return parent::reverseTransform($value);
        var_dump($value);
        return $value;
        if ($value === null)
            return false;

        if (!\is_int($value))
            throw new TransformationFailedException('Expected an Integer.');

        return $value != 0;
    }

    public function reverseTransform($value): string
    {
        return parent::transform($value);

        var_dump($value);
        return $value;
        if ($value === null)
            return 0;

        if (!\is_bool($value))
            throw new TransformationFailedException('Expected a Boolean.');

        return $value ? 1 : 0;
    }
}
