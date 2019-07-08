<?php

namespace AppBundle\Extractor;

use TwitterOAuth\Exception\TwitterException;
use TwitterOAuth\TwitterOAuth;

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

        if (null === $host || null === $path) {
            return false;
        }

        // find tweet id
        preg_match('/([0-9]{18,})/', $path, $matches);

        if (!\in_array($host, ['mobile.twitter.com', 'twitter.com'], true) || !isset($matches[1])) {
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
        $data = $this->retrieveTwitterData();

        if (false === $data) {
            return '';
        }

        return $this->retrieveTweet($data);
    }

    /**
     * Retrieve tweet information from twitter.
     *
     * @return array|false
     */
    public function retrieveTwitterData()
    {
        if (!$this->tweetId) {
            return false;
        }

        try {
            return $this->twitter->get('statuses/show', ['id' => $this->tweetId, 'tweet_mode' => 'extended']);
        } catch (TwitterException $e) {
            $this->logger->warning('Twitter extract failed for: ' . $this->tweetId, [
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Return a tweet ready to be displayed.
     *
     * @param array $data
     *
     * @return string
     */
    private function retrieveTweet($data)
    {
        $tweet = '<p><strong>' . $data['user']['name'] . '</strong>';
        $tweet .= ' &ndash; <a href="https://twitter.com/' . $data['user']['screen_name'] . '">@' . $data['user']['screen_name'] . '</a>';
        $tweet .= '<br/>' . nl2br($data['full_text']);
        $tweet .= '<br/><em>' . $data['created_at'] . '</em></p>';

        // replace links with real links
        foreach ($data['entities']['urls'] as $url) {
            $tweet = str_ireplace($url['url'], '<a href="' . $url['expanded_url'] . '">' . $url['display_url'] . '</a>', $tweet);
        }

        // replace users with real links
        foreach ($data['entities']['user_mentions'] as $user) {
            $tweet = str_ireplace('@' . $user['screen_name'], '<a href="https://twitter.com/' . $user['screen_name'] . '">@' . $user['screen_name'] . '</a>', $tweet);
        }

        // replace hashtags with real links
        foreach ($data['entities']['hashtags'] as $hashtag) {
            $tweet = str_ireplace('#' . $hashtag['text'], '<a href="https://twitter.com/hashtag/' . $hashtag['text'] . '?src=hash">#' . $hashtag['text'] . '</a>', $tweet);
        }

        // insert medias
        if (isset($data['extended_entities']['media'])) {
            foreach ($data['extended_entities']['media'] as $media) {
                // remove link to that media from the tweet (to avoid confusion)
                $tweet = str_ireplace($media['url'], '', $tweet);

                $tweet .= '<p><img src="' . $media['media_url_https'] . '" /></p>';
            }
        }

        // is there a quoted status?
        if (isset($data['quoted_status'])) {
            $tweet .= $this->retrieveTweet($data['quoted_status']);
        }

        return $tweet;
    }
}
