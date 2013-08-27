<?php

namespace j0k3r\FeedBundle\Parser;

/**
 * PinboardInParser
 *
 * This class provides a custom parser for pinboard popular feeds
 */
class PinboardInParser extends DefaultParser
{
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
        return $this->itemContent.'<hr/><br/>'.$content;
    }
}
