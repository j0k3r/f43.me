<?php

namespace Api43\FeedBundle\EventListener;

use Api43\FeedBundle\Event\FeedItemEvent;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class FeedItemSubscriber
{
    protected $hub = '';
    protected $router;

    /**
     * Create a new subscriber.
     *
     * @param string $hub A hub (url) to ping
     */
    public function __construct($hub, Router $router)
    {
        $this->hub = $hub;
        $this->router = $router;
    }

    /**
     * Ping available hub when new items are cached.
     *
     * http://nathangrigg.net/2012/09/real-time-publishing/
     *
     * @param  FeedItemEvent $event
     * @return bool
     */
    public function pingHub(FeedItemEvent $event)
    {
        if (empty($this->hub)) {
            return false;
        }

        // retrieve feed urls
        $slugs = $event->getFeedSlugs();

        $urls = array();
        foreach ($slugs as $slug) {
            $urls[] = $this->router->generate(
                'feed_xml',
                array('slug' => $slug),
                true
            );
        }

        // ping publisher
        // https://code.google.com/p/pubsubhubbub/source/browse/trunk/publisher_clients/php/library/publisher.php
        $params = "hub.mode=publish";
        foreach ($urls as $url) {
            $params .= "&hub.url=" . urlencode($url);
        }

        // make the request
        $options = array(
            CURLOPT_URL        => $this->hub,
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_USERAGENT  => "PubSubHubbub-Publisher-PHP/1.0"
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_exec($ch);

        // receive the response
        $info = curl_getinfo($ch);
        curl_close($ch);

        // hub doesn't respond correctly, do something ?
        return !($info['http_code'] != 204);
    }
}
