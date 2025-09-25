<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ItemsCachedEvent extends Event
{
    public const NAME = 'item.cached';

    /**
     * Store slug feeds that need to be dispatched.
     */
    public function __construct(protected array $feedSlugs)
    {
    }

    public function getFeedSlugs(): array
    {
        return $this->feedSlugs;
    }
}
