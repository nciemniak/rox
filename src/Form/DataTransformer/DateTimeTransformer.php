<?php

namespace App\Form\DataTransformer;

use DateTime;
use Symfony\Component\Form\DataTransformerInterface;

class DateTimeTransformer implements DataTransformerInterface
{
    public function transform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value->format('Y-m-d');
    }

    public function reverseTransform($value): ?DateTime
    {
        if (null === $value) {
            return null;
        }

        $setDate = DateTime::createFromFormat('Y-m-d', $value);

        if (false === $setDate) {
            return null;
        }

        return $setDate;
    }
}
