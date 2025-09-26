<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ConstraintRss extends Constraint
{
    public function validatedBy(): string
    {
        return 'valid_rss';
    }
}
