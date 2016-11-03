<?php

namespace Api43\FeedBundle\EventListener;

use Api43\FeedBundle\Event\FeedItemEvent;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedItemSubscriber
{
    protected $hub = '';
    protected $router;
    protected $client;

    /**
     * Create a new subscriber.
     *
     * @param string $hub    A hub (url) to ping
     * @param Router $router Symfony Router to generate the feed xml
     * @param Client $client Guzzle client to send the request
     */
    public function __construct($hub, Router $router, Client $client)
    {
        $this->hub = $hub;
        $this->router = $router;
        $this->client = $client;
    }

    /**
     * Ping available hub when new items are cached.
     *
     * http://nathangrigg.net/2012/09/real-time-publishing/
     *
     * @param FeedItemEvent $event
     *
     * @return bool
     */
    public function pingHub(FeedItemEvent $event)
    {
        if (empty($this->hub)) {
            return false;
        }

        // retrieve feed urls
        $slugs = $event->getFeedSlugs();

        $urls = [];
        foreach ($slugs as $slug) {
            $urls[] = $this->router->generate(
                'feed_xml',
                ['slug' => $slug],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        // ping publisher
        // https://github.com/pubsubhubbub/php-publisher/blob/master/library/publisher.php
        $params = 'hub.mode=publish';
        foreach ($urls as $url) {
            $params .= '&hub.url=' . $url;
        }

        $response = $this->client->post(
            $this->hub,
            [
                'exceptions' => false,
                'body' => $params,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => 'PubSubHubbub-Publisher-PHP/1.0',
                ],
            ]
        );

        // hub should response 204 if everything went fine
        return !($response->getStatusCode() !== 204);
    }
}
