<?php

namespace j0k3r\FeedBundle\EventListener;

use j0k3r\FeedBundle\Event\FeedItemEvent;

class FeedItemSubscriber
{
    protected $hub = array();

    public function __construct($hub)
    {
        $this->hub = $hub;
    }

    /**
     * Ping available hub when new items are cached.
     *
     * http://nathangrigg.net/2012/09/real-time-publishing/
     *
     * @param   FeedItemEvent $event
     * @return  true/false
     */
    public function pingHub(FeedItemEvent $event)
    {
        if (empty($this->hub)) {
            return false;
        }

        // retrieve feed urls
        $urls = $event->getFeedUrls();

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

        if ($info['http_code'] != 204) {
            // hub doesn't respond correctly, do something ?
            return false;
        }
        return true;
    }
}
