<?php

namespace App\Tests;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Mock\Client as HttpMockClient;
use PHPUnit\Framework\TestCase;

class AppTestCase extends TestCase
{
    /**
     * Return a mocked client with some responses.
     *
     * @return HttpMethodsClient
     */
    public static function getMockClient(array $responses = [])
    {
        $httpMockClient = new HttpMockClient();

        foreach ($responses as $response) {
            $httpMockClient->addResponse($response);
        }

        $pluginClient = new PluginClient($httpMockClient, [(new ErrorPlugin())]);

        return new HttpMethodsClient($pluginClient, MessageFactoryDiscovery::find());
    }
}
