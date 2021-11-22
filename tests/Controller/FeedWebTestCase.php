<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

class FeedWebTestCase extends WebTestCase
{
    public function getAuthorizedClient(): KernelBrowser
    {
        $client = static::createClient();

        $client->loginUser(new InMemoryUser('admin', 'testadmin', ['ROLE_ADMIN']));

        return $client;
    }
}
