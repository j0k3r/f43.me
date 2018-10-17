<?php

namespace Tests\AppBundle\Converter;

use AppBundle\Converter\Instagram;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class InstagramTest extends TestCase
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
                '<a target="_blank" href="https://www.instagram.com/pli.panda/">',
                '<a target="_blank" href="https://www.instagram.com/pli.panda/">',
                '',
                0,
            ],
            [
                '<div class="f43me-instagram-extracted"><h2>Photo @ladzinski</h2><p><img src="https://scontent-amt2-1.cdninstagram.com/t51.2885-15/sh0.08/e35/p640x640/16464714_735605356614941_9152627590412894208_n.jpg"></p><blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="7" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"> <div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding:62.4537037037037% 0; text-align:center; width:100%;"> <div style=" background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAMUExURczMzPf399fX1+bm5mzY9AMAAADiSURBVDjLvZXbEsMgCES5/P8/t9FuRVCRmU73JWlzosgSIIZURCjo/ad+EQJJB4Hv8BFt+IDpQoCx1wjOSBFhh2XssxEIYn3ulI/6MNReE07UIWJEv8UEOWDS88LY97kqyTliJKKtuYBbruAyVh5wOHiXmpi5we58Ek028czwyuQdLKPG1Bkb4NnM+VeAnfHqn1k4+GPT6uGQcvu2h2OVuIf/gWUFyy8OWEpdyZSa3aVCqpVoVvzZZ2VTnn2wU8qzVjDDetO90GSy9mVLqtgYSy231MxrY6I2gGqjrTY0L8fxCxfCBbhWrsYYAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-22px; width:44px;"></div></div> <p style=" margin:8px 0 0 0; padding:0 4px;"> <img src="https://scontent-amt2-1.cdninstagram.com/t51.2885-15/sh0.08/e35/p640x640/16464714_735605356614941_9152627590412894208_n.jpg" /></p><p><a href="https://www.instagram.com/p/BQDVfhnlC2P/" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">Photo @ladzinski</a></p> <p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">A post shared by National Geographic (@natgeo) on <time style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px;" datetime="2017-02-03T14:04:05+00:00">Feb 3, 2017 at 6:04am PST</time></p></div></blockquote><script async defer src="//platform.instagram.com/en_US/embeds.js"></script></div>',
                '<div class="f43me-instagram-extracted"><h2>Photo @ladzinski</h2><p><img src="https://scontent-amt2-1.cdninstagram.com/t51.2885-15/sh0.08/e35/p640x640/16464714_735605356614941_9152627590412894208_n.jpg"></p><blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="7" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"> <div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding:62.4537037037037% 0; text-align:center; width:100%;"> <div style=" background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAMUExURczMzPf399fX1+bm5mzY9AMAAADiSURBVDjLvZXbEsMgCES5/P8/t9FuRVCRmU73JWlzosgSIIZURCjo/ad+EQJJB4Hv8BFt+IDpQoCx1wjOSBFhh2XssxEIYn3ulI/6MNReE07UIWJEv8UEOWDS88LY97kqyTliJKKtuYBbruAyVh5wOHiXmpi5we58Ek028czwyuQdLKPG1Bkb4NnM+VeAnfHqn1k4+GPT6uGQcvu2h2OVuIf/gWUFyy8OWEpdyZSa3aVCqpVoVvzZZ2VTnn2wU8qzVjDDetO90GSy9mVLqtgYSy231MxrY6I2gGqjrTY0L8fxCxfCBbhWrsYYAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-22px; width:44px;"></div></div> <p style=" margin:8px 0 0 0; padding:0 4px;"> <img src="https://scontent-amt2-1.cdninstagram.com/t51.2885-15/sh0.08/e35/p640x640/16464714_735605356614941_9152627590412894208_n.jpg" /></p><p><a href="https://www.instagram.com/p/BQDVfhnlC2P/" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">Photo @ladzinski</a></p> <p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">A post shared by National Geographic (@natgeo) on <time style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px;" datetime="2017-02-03T14:04:05+00:00">Feb 3, 2017 at 6:04am PST</time></p></div></blockquote><script async defer src="//platform.instagram.com/en_US/embeds.js"></script></div>',
                '',
                0,
            ],
            [
                '<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="7" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"> <div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding:62.4537037037037% 0; text-align:center; width:100%;"> <div style=" background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAMUExURczMzPf399fX1+bm5mzY9AMAAADiSURBVDjLvZXbEsMgCES5/P8/t9FuRVCRmU73JWlzosgSIIZURCjo/ad+EQJJB4Hv8BFt+IDpQoCx1wjOSBFhh2XssxEIYn3ulI/6MNReE07UIWJEv8UEOWDS88LY97kqyTliJKKtuYBbruAyVh5wOHiXmpi5we58Ek028czwyuQdLKPG1Bkb4NnM+VeAnfHqn1k4+GPT6uGQcvu2h2OVuIf/gWUFyy8OWEpdyZSa3aVCqpVoVvzZZ2VTnn2wU8qzVjDDetO90GSy9mVLqtgYSy231MxrY6I2gGqjrTY0L8fxCxfCBbhWrsYYAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-22px; width:44px;"></div></div> <p style=" margin:8px 0 0 0; padding:0 4px;"> <a href="https://www.instagram.com/p/BQDVfhnlC2P/" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">Photo @ladzinski / It&#39;s good to feel small from time to time and stepping foot into the #GiantForest of #sequoianationalpark, you certainly do. Sequoia trees are among the oldest living trees on earth, some living in excess of 3,000 years old. You don&#39;t get this old without being resilient. Sequoias are a very thirsty tree so to speak, requiring a lot of water. In recent decades heat index has been slowly on the on the rise, coupled with the drought in California, is making life at lower elevations difficult for sequoia&#39;s. Some are showing signs of stress, shedding needles in an effort to fight the heat. Photographed #onassignment for @natgeo</a></p> <p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">Une photo publiée par National Geographic (@natgeo) le <time style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px;" datetime="2017-02-03T14:04:05+00:00">3 Févr. 2017 à 6h04 PST</time></p></div></blockquote> <script async defer src="//platform.instagram.com/en_US/embeds.js"></script>',
                '<img src="https://scontent-cdg2-1.cdninstagram.com/t51.2885-15/e35/16464714_735605356614941_9152627590412894208_n.jpg" />',
                'BQDVfhnlC2P',
                1,
            ],
            [
                // from http://www.fubiz.net/2017/02/20/original-and-vertiginous-staircases-photography/
                ';"><a style="color: #000; font-family: Arial,sans-serif; font-size: 14px; font-style: normal; font-weight: normal; line-height: 17px; text-decoration: none; word-wrap: break-word;" href="https://www.instagram.com/p/BQit0OfF50t/" target="_blank">Neon',
                '<img src="https://scontent-cdg2-1.cdninstagram.com/t51.2885-15/e35/16464714_735605356614941_9152627590412894208_n.jpg" />',
                'BQit0OfF50t',
                1,
            ],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($html, $expected, $instagramId, $instaExtractorOccurence)
    {
        $instaExtractor = $this->getMockBuilder('AppBundle\Extractor\Instagram')
            ->disableOriginalConstructor()
            ->getMock();

        $instaExtractor->expects($this->exactly($instaExtractorOccurence))
            ->method('match')
            ->with('https://www.instagram.com/p/' . $instagramId . '/')
            ->will($this->returnValue(true));

        $instaExtractor->expects($this->exactly($instaExtractorOccurence))
            ->method('getImageOnly')
            ->will($this->returnValue('https://scontent-cdg2-1.cdninstagram.com/t51.2885-15/e35/16464714_735605356614941_9152627590412894208_n.jpg'));

        $instaConverter = new Instagram($instaExtractor);
        $instaConverter->setLogger(new NullLogger());
        $this->assertContains($expected, $instaConverter->convert($html));
    }

    public function testMatchButInstaExtractFail()
    {
        $html = '<a href="https://www.instagram.com/p/BQDVfhnlC2P/" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">Photo</a>';

        $instaExtractor = $this->getMockBuilder('AppBundle\Extractor\Instagram')
            ->disableOriginalConstructor()
            ->getMock();

        $instaExtractor->expects($this->once())
            ->method('match')
            ->will($this->returnValue(true));

        $instaExtractor->expects($this->once())
            ->method('getImageOnly')
            ->will($this->returnValue(''));

        $instaConverter = new Instagram($instaExtractor);
        $instaConverter->setLogger(new NullLogger());

        $this->assertSame($html, $instaConverter->convert($html));
    }
}
