<?php

namespace Api43\FeedBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FeedWebTestCase extends WebTestCase
{
    public function getAuthorizedClient(array $options = [])
    {
        $options += array(
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'adminpass',
        );

        return static::createClient([], $options);
    }
}
