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

        return $this->buildTweetDisplay($data);
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
            $tweet = $this->twitter->get('statuses/show', ['id' => $this->tweetId, 'tweet_mode' => 'extended']);

            return $this->retrieveThread($tweet);
        } catch (TwitterException $e) {
            $this->logger->warning('Twitter extract failed for: ' . $this->tweetId, [
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Find the next tweet in reply to the give one for the same user.
     *
     * @param array $tweet        Tweet to find a reply for
     * @param array $threadTweets Tweets collected for the current thread
     *
     * @return array All tweets in that thread (if it's a thread)
     *               Otherwise it'll return the given tweet
     */
    private function retrieveThread($tweet, $threadTweets = [])
    {
        $threadTweets[] = $tweet;

        $replies = $this->twitter->get('search/tweets', [
            'q' => 'to:' . $tweet['user']['screen_name'],
            'since_id' => $tweet['id'],
            // 'max_id' => $maxId,
            'count' => 100,
        ]);

        foreach ($replies['statuses'] as $reply) {
            if ($reply['in_reply_to_status_id'] === $tweet['id'] && $tweet['user']['id'] === $reply['user']['id']) {
                // retrieve single reply with the show api to have the `full_text` field
                $replyTweet = $this->twitter->get('statuses/show', [
                    'id' => $reply['id'],
                    'tweet_mode' => 'extended',
                ]);

                return $this->retrieveThread($replyTweet, $threadTweets);
            }
        }

        return $threadTweets;
    }

    /**
     * Return a tweet ready to be displayed.
     *
     * @param array $data An array of tweets to be displayed
     *
     * @return string
     */
    private function buildTweetDisplay($tweets)
    {
        $html = '';
        foreach ($tweets as $tweet) {
            $html .= '<p><strong>' . $tweet['user']['name'] . '</strong>';
            $html .= ' &ndash; <a href="https://twitter.com/' . $tweet['user']['screen_name'] . '">@' . $tweet['user']['screen_name'] . '</a>';
            $html .= '<br/>' . nl2br($tweet['full_text']);
            $html .= '<br/><em>' . $tweet['created_at'] . '</em></p>';

            // replace links with real links
            foreach ($tweet['entities']['urls'] as $url) {
                $html = str_ireplace($url['url'], '<a href="' . $url['expanded_url'] . '">' . $url['display_url'] . '</a>', $html);
            }

            // replace users with real links
            foreach ($tweet['entities']['user_mentions'] as $user) {
                $html = str_ireplace('@' . $user['screen_name'], '<a href="https://twitter.com/' . $user['screen_name'] . '">@' . $user['screen_name'] . '</a>', $html);
            }

            // replace hashtags with real links
            foreach ($tweet['entities']['hashtags'] as $hashtag) {
                $html = str_ireplace('#' . $hashtag['text'], '<a href="https://twitter.com/hashtag/' . $hashtag['text'] . '?src=hash">#' . $hashtag['text'] . '</a>', $html);
            }

            // insert medias
            if (isset($tweet['extended_entities']['media'])) {
                foreach ($tweet['extended_entities']['media'] as $media) {
                    // remove link to that media from the tweet (to avoid confusion)
                    $html = str_ireplace($media['url'], '', $html);

                    $html .= '<p><img src="' . $media['media_url_https'] . '" /></p>';
                }
            }

            // is there a quoted status?
            if (isset($tweet['quoted_status'])) {
                $html .= $this->buildTweetDisplay([$tweet['quoted_status']]);
            }
        }

        return $html;
    }
}
