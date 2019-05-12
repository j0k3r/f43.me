<?php

namespace AppBundle\Validator\Constraints;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ConstraintRss extends Constraint
{
    public function validatedBy()
    {
        return 'valid_rss';
    }
}
