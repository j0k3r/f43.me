<?php

namespace App\Validator\Constraints;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ConstraintRss extends Constraint
{
    public function validatedBy(): string
    {
        return 'valid_rss';
    }
}
