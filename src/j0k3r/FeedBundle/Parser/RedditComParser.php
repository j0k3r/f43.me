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
