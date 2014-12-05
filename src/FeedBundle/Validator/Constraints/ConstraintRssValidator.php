<?php

namespace Api43\FeedBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

/**
 * Validate a XML feed.
 * Wait 3s before timeout
 */
class ConstraintRssValidator extends ConstraintValidator
{
    private $guzzle;

    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        try {
            $content = $this->guzzle
                ->get('http://validator.w3.org/feed/check.cgi?url='.$value)
                ->send()
                ->getBody(true);
        } catch (RequestException $e) {
            // if thing goes wrong, let's try with an alternative
            try {
                $content = $this->guzzle
                    ->get('http://feedvalidator.org/check.cgi?url='.$value)
                    ->send()
                    ->getBody(true);
            } catch (RequestException $e) {
                $content = false;
            }
        }

        // if content is still false, we won't invalidate the feed, it means both alternative to check are unavailable
        if (false !== $content && 0 === preg_match('/This is a valid/', $content)) {
            $this->context->addViolation($constraint->message, array('%string%' => $value));
        }
    }
}
