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
     * @return  void
     */
    public function pingHub(FeedItemEvent $event)
    {
        if (empty($this->hub)) {
            return;
        }

        // retrieve feed urls
        $urls = $event->getFeedUrls();

        // ping them all !
        // http://www.fusionswift.com/2011/08/php-curl_multi_exec-example/
        $curl_array = array();
        $ch = curl_multi_init();

        foreach($urls as $key => $url) {
            $curl_array[$key] = curl_init();

            $data = array(
                'hub.mode' => 'publish',
                'hub.url'  => $url,
            );

            curl_setopt($curl_array[$key], CURLOPT_URL, $this->hub);
            curl_setopt($curl_array[$key], CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl_array[$key], CURLOPT_HEADER, TRUE);
            curl_setopt($curl_array[$key], CURLOPT_NOBODY, TRUE);
            curl_setopt($curl_array[$key], CURLOPT_POST, true);
            curl_setopt($curl_array[$key], CURLOPT_POSTFIELDS, $data);

            curl_multi_add_handle($ch, $curl_array[$key]);
        }

        do {
            curl_multi_exec($ch, $exec);
        } while($exec > 0);

        foreach($urls as $key => $url) {
            $response = curl_multi_getcontent($curl_array[$key]);

            if (!preg_match('/(HTTP\/1\.1 204 No Content)/i', $response)) {
                // hub doesn't respond correctly, do something ?
            }
        }

        foreach($urls as $key => $url) {
            curl_multi_remove_handle($ch, $curl_array[$key]);
        }

        curl_multi_close($ch);

        foreach($urls as $key => $url) {
            curl_close($curl_array[$key]);
        }
    }
}
