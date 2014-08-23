<?php

namespace j0k3r\FeedBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FeedWebTestCase extends WebTestCase
{
    public function getAuthorizedClient(array $options = array())
    {
        $options += array(
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW'   => 'adminpass',
        );

        return static::createClient(array(), $options);
    }
}
