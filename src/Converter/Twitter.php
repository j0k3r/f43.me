<?php

namespace App\Converter;

use App\Extractor\Twitter as TwitterExtractor;

/**
 * This converter will try to change pic.twitter.com image link from Twitter to a real image to be displayed.
 * It'll also convert embedded t.co link to real link.
 */
class Twitter extends AbstractConverter
{
    public const IMAGE_CONTENT = '<img src="image_url" /></p><p>';

    public function __construct(private readonly TwitterExtractor $twitterExtractor)
    {
    }

    /**
     * This will convert all pic.twitter.com into real image.
     * We retrieve all link to a twitter status and if that status contains some medias and that media url (pic.twitter.com/XXXX) exist in the html
     * we repalce it by an img tag.
     */
    public function convert(string $html): string
    {
        $re = '/<a href\=\"https:\/\/twitter.com\/([a-z0-9\-\_]+)\/status\/([0-9]{18})\"/i';
        $res = preg_match_all($re, $html, $matches);

        if (false === $res || $res < 1) {
            return $html;
        }

        foreach ($matches[2] as $twitterId) {
            $this->twitterExtractor->match('https://twitter.com/username/' . $twitterId);
            $data = $this->twitterExtractor->retrieveTwitterData();

            if (false === $data) {
                continue;
            }

            if (!empty($data['entities']['media'])) {
                foreach ($data['entities']['media'] as $mediaData) {
                    $pic = $mediaData['display_url'];
                    if (false === stripos($html, (string) $pic)) {
                        continue;
                    }

                    $html = str_ireplace($pic, '<br /><img src="' . $mediaData['media_url_https'] . '" />', $html);
                }
            }

            foreach ($data['entities']['urls'] as $url) {
                $html = str_ireplace($url['url'], $url['expanded_url'], $html);
            }
        }

        return $html;
    }
}
