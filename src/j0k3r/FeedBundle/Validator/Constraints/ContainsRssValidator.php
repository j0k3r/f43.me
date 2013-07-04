<?php

namespace j0k3r\FeedBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate a XML feed.
 * Wait 3s before timeout
 */
class ContainsRssValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://validator.w3.org/feed/check.cgi?url='.$value);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'f43.me');
        // wait 3s before time out
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $content = curl_exec($ch);
        curl_close($ch);

        // if content is false, it means the request timeout, let's try with an alternative
        if (false === $content) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://feedvalidator.org/check.cgi?url='.$value);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'f43.me');
            // wait 3s before time out
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $content = curl_exec($ch);
            curl_close($ch);
        }

        // if content is still false, we won't invalidate the feed, it means both alternative to check are unavailable
        if (false !== $content && 0 === preg_match('/This is a valid/', $content)) {
            $this->context->addViolation($constraint->message, array('%string%' => $value));
        }
    }
}
