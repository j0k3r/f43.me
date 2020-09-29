<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FeedWebTestCase extends WebTestCase
{
    public function getAuthorizedClient(array $options = []): KernelBrowser
    {
        $options += [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'adminpass',
        ];

        return static::createClient([], $options);
    }
}
