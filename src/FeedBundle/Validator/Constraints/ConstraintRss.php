<?php

namespace Api43\FeedBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Doctrine\Common\Annotations\Annotation;

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
