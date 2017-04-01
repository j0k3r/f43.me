<?php

namespace Tests\FeedBundle\Converter;

use Api43\FeedBundle\Converter\Youtube;
use Psr\Log\NullLogger;

class YoutubeTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            [
                '<div></div>',
                '<div></div>',
                '',
                0,
            ],
            [
                '[embedded content, src: "awesome_embed"]',
                '[embedded content, src: "awesome_embed"]',
                '',
                0,
            ],
            [
                // form http://www.mac4ever.com/actu/119826_mac4ever-cap-vers-le-futur-video
                '[embedded content, src: "https://www.youtube.com/embed/0ZkfxIpEsZs?rel=0"]',
                '<iframe width="480" height="270" src="https://www.youtube.com/embed/0ZkfxIpEsZs?rel=0" frameborder="0" allowfullscreen></iframe>',
                'https://www.youtube.com/embed/0ZkfxIpEsZs?rel=0',
                1,
            ],
            [
                // from http://www.fubiz.net/2017/02/20/original-and-vertiginous-staircases-photography/
                '<div class="clearfix">[embedded content, src: "https://www.youtube.com/embed/phETSsuUMsw"]</div>',
                '<div class="clearfix"><iframe width="480" height="270" src="https://www.youtube.com/embed/phETSsuUMsw" frameborder="0" allowfullscreen></iframe></div>',
                'https://www.youtube.com/embed/phETSsuUMsw',
                1,
            ],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($html, $expected, $youtubeUrl, $youtubeExtractorOccurence)
    {
        $youtubeExtractor = $this->getMockBuilder('Api43\FeedBundle\Extractor\Youtube')
            ->disableOriginalConstructor()
            ->getMock();

        $youtubeExtractor->expects($this->exactly($youtubeExtractorOccurence))
            ->method('match')
            ->with($youtubeUrl)
            ->will($this->returnValue(true));

        $youtubeConverter = new Youtube($youtubeExtractor);
        $youtubeConverter->setLogger(new NullLogger());
        $this->assertContains($expected, $youtubeConverter->convert($html));
    }

    public function testMatchButYoutubeUrlNotMatch()
    {
        $html = '<div class="clearfix">[embedded content, src: "https://www.youtube.com/embed/phETSsuUMsw"]</div>';

        $youtubeExtractor = $this->getMockBuilder('Api43\FeedBundle\Extractor\Youtube')
            ->disableOriginalConstructor()
            ->getMock();

        $youtubeExtractor->expects($this->once())
            ->method('match')
            ->will($this->returnValue(false));

        $youtubeConverter = new Youtube($youtubeExtractor);
        $youtubeConverter->setLogger(new NullLogger());

        $this->assertEquals($html, $youtubeConverter->convert($html));
    }
}
