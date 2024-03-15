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
    private $client;

    public function __construct(HttpMethodsClientInterface $client)
    {
        $this->client = $client;
    }

    public function validate($value, Constraint $constraint): void
    {
        // reddit feeds are not valid RSS
        if (str_contains($value, 'reddit.com')) {
            return;
        }

        try {
            $content = $this->client
                ->get('https://validator.w3.org/feed/check.cgi?url=' . $value)
                ->getBody();
        } catch (RequestException $e) {
            try {
                $content = $this->client
                    ->get('https://www.rssboard.org/rss-validator/check.cgi?url=' . $value)
                    ->getBody();
            } catch (RequestException $e) {
                $content = false;
            }
        }

        // if content is still false, we won't invalidate the feed, it means both alternative to check are unavailable
        if (false !== $content && 0 === preg_match('/This is a valid/', $content)) {
            $this->context->addViolation('Feed "%string%" is not valid.', ['%string%' => $value]);
        }
    }
}
