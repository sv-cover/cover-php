<?php 
namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class IntToBooleanTransformer implements DataTransformerInterface
{
    public function transform($value): bool
    {
        if (!\is_int($value))
            throw new TransformationFailedException('Expected an Integer.');

        return $value != 0;
    }

    public function reverseTransform($value): int
    {
        if (!\is_bool($value))
            throw new TransformationFailedException('Expected a Boolean.');

        return $value ? 1 : 0;
    }
}
