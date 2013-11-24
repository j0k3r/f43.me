<?php

namespace j0k3r\FeedBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FeedWebTestCase extends WebTestCase
{
    public function getClient(array $options = array())
    {
        $options += array('HTTP_HOST' => 'f43me.dev');

        return static::createClient(array(), $options);
    }

    public function getAuthorizedClient(array $options = array())
    {
        $options += array(
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW'   => 'adminpass',
        );

        return static::getClient($options);
    }
}
