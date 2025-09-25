<?php

namespace App\Message;

class FeedSync
{
    public function __construct(private readonly int $feedId)
    {
    }

    public function getfeedId(): int
    {
        return $this->feedId;
    }
}
