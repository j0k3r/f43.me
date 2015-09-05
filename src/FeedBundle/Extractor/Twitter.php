<?php

namespace Api43\FeedBundle\Extractor;

use TwitterOAuth\TwitterOAuth;
use TwitterOAuth\Exception\TwitterException;

class Twitter extends AbstractExtractor
{
    protected $twitter;
    protected $tweetId = null;

    public function __construct(TwitterOAuth $twitter)
    {
        $this->twitter = $twitter;
    }

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $host || false === $path) {
            return false;
        }

        // find tweet id
        preg_match('/([0-9]{18})/', $path, $matches);

        if (!in_array($host, array('mobile.twitter.com', 'twitter.com')) || !isset($matches[1])) {
            return false;
        }

        $this->tweetId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->tweetId) {
            return '';
        }

        try {
            $twitterData = $this->twitter->get('statuses/show', array('id' => $this->tweetId));
        } catch (TwitterException $e) {
            $this->logger->warning('Twitter extract failed for: '.$this->tweetId, array(
                'exception' => $e,
            ));

            return '';
        }

        $tweet = '<p><strong>'.$twitterData['user']['name'].'</strong>';
        $tweet .= ' &ndash; @'.$twitterData['user']['screen_name'];
        $tweet .= '<br/>'.$twitterData['text'];
        $tweet .= '<br/><em>'.$twitterData['created_at'].'</em></p>';

        if (!isset($twitterData['extended_entities']['media'])) {
            return $tweet;
        }

        foreach ($twitterData['extended_entities']['media'] as $media) {
            $tweet .= '<p><img src="'.$media['media_url_https'].'" /></p>';
        }

        return $tweet;
    }
}
