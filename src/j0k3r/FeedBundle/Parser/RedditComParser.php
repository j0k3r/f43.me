<?php

namespace j0k3r\FeedBundle\Parser;

/**
 * RedditComParser
 *
 * This class provides a custom parser for reddit feeds
 */
class RedditComParser extends DefaultParser
{
    /**
     * For reddit we extract link for the default content retrieved.
     * Because the rss item link goes to reddit. The important one is inside the content.
     *
     * @see DefaultParser/retrieveUrl
     *
     * @return string Url to be used to retrieve content
     */
    public function retrieveUrl()
    {
        // we extract the source of the reddit post
        preg_match('/(.*)\<a href\=\"(.*)\"\>\[link\]\<\/a\>/i', $this->itemContent, $matches);
        if (count($matches) != 3) {
            return $this->url;
        }

        return $matches[2];
    }

    /**
     * We just happen the readable item to the default one
     *
     * @see DefaultParser/updateContent
     *
     * @param string $content Readable item content
     *
     * @return string
     */
    public function updateContent($content)
    {
        return $this->itemContent.'<br/><hr/><br/>'.$content;
    }
}
