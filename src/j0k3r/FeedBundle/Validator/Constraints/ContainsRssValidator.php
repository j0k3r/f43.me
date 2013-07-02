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
        libxml_use_internal_errors(true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $value);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // wait 3s before time out
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $content = curl_exec($ch);
        curl_close($ch);

        $dom = new \DOMDocument;
        $dom->load($content);

        if (false === $dom->validate()) {
            $this->context->addViolation($constraint->message, array('%string%' => $value));
        }
    }
}
