<?php

namespace Api43\FeedBundle\Converter;

use Api43\FeedBundle\Extractor\Instagram as InstagramExtractor;

/**
 * This converter will try to change embed html from Instagram from a simple html code to a real image to be displayed.
 */
class Instagram extends AbstractConverter
{
    const IMAGE_CONTENT = '<img src="image_url" /></p><p>';

    private $instagramExtractor;

    public function __construct(InstagramExtractor $instagramExtractor)
    {
        $this->instagramExtractor = $instagramExtractor;
    }

    /**
     * This will convert all instagram embed to real image.
     * The detection is a bit ugly because it's done using a regex to find instagram content.
     * Mostly because html shouldn't be valid.
     *
     * @param string $html
     *
     * @return string
     */
    public function convert($html)
    {
        $re = '/<a([0-9a-z-_:\.\=\"\; ,#]*)href\=\"https\:\/\/www.instagram.com\/p\/([0-9a-z-_:\/\.]+)\"/i';
        $res = preg_match_all($re, $html, $matches);

        if (false === $res || $res < 1) {
            return $html;
        }

        foreach ($matches[2] as $key => $instagramId) {
            $this->instagramExtractor->match('https://www.instagram.com/p/' . $instagramId);
            $image = $this->instagramExtractor->getImageOnly();

            if (strlen($image) === 0) {
                continue;
            }

            $newContent = str_replace('image_url', $image, self::IMAGE_CONTENT);

            $html = str_replace($matches[0][$key], $newContent . $matches[0][$key], $html);
        }

        return $html;
    }
}
