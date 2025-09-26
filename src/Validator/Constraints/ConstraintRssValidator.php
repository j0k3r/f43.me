<?php

namespace App\Validator\Constraints;

use Http\Client\Common\HttpMethodsClientInterface;
use Http\Client\Exception\RequestException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate a XML feed.
 * Wait 3s before timeout.
 */
class ConstraintRssValidator extends ConstraintValidator
{
    public function __construct(private readonly HttpMethodsClientInterface $client)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        try {
            $content = $this->client
                ->get('https://validator.w3.org/feed/check.cgi?url=' . $value)
                ->getBody();
        } catch (RequestException) {
            // if thing goes wrong, let's try with an alternative
            try {
                $content = $this->client
                    ->get('https://www.rssboard.org/rss-validator/check.cgi?url=' . $value)
                    ->getBody();
            } catch (RequestException) {
                $content = false;
            }
        }

        // if content is still false, we won't invalidate the feed, it means both alternative to check are unavailable
        if (false !== $content && 0 === preg_match('/This is a valid/', $content)) {
            $this->context->addViolation('Feed "%string%" is not valid.', ['%string%' => $value]);
        }
    }
}
