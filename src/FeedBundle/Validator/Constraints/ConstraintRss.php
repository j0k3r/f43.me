<?php

namespace Api43\FeedBundle\Validator\Constraints;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ConstraintRss extends Constraint
{
    public $message = 'Feed "%string%" is not valid.';

    public function validatedBy()
    {
        return 'valid_rss';
    }
}
