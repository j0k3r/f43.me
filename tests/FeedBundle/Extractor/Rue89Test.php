<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Rue89;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class Rue89Test extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return array(
            array('http://rue89.nouvelobs.com/2015/10/26/algorithmes-antimensonge-fin-bobards-politique-261827', true),
            array('http://api.rue89.nouvelobs.com/2015/10/26/ils-protegent-vie-privee-autres-prejuges-les-allemands-261832', true),
            array('https://api.rue89.nouvelobs.com/2015/10/26/ils-protegent-vie-privee-autres-prejuges-les-allemands-261832', true),
            array('http://rue89.nouvelobs.com/2015/10/26/algorithmes-antimensonge-fin-bobards-politique-261827.html', false),
            array('https://goog.co', false),
            array('http://user@:80', false),
        );
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $rue89 = new Rue89();
        $this->assertEquals($expected, $rue89->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode(array('node' => array('title' => 'my title', 'intro' => 'my description', 'imgTabletteCarousel' => 'http://0.0.0.0/img.jpg', 'body' => '<iframe/>'))))),
            new Response(200, [], Stream::factory('')),
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $rue89 = new Rue89();
        $rue89->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', array($logHandler));
        $rue89->setLogger($logger);

        // first test fail because we didn't match an url, so Rue89Url isn't defined
        $this->assertEmpty($rue89->getContent());

        $rue89->match('http://rue89.nouvelobs.com/2015/10/26/algorithmes-antimensonge-fin-bobards-politique-261827');

        // consecutive calls
        $this->assertEquals('<div><p>my description</p><p><img src="http://0.0.0.0/img.jpg"></p><iframe/></div>', $rue89->getContent());
        // this one will got an empty array
        $this->assertEmpty($rue89->getContent());
        // this one will catch an exception
        $this->assertEmpty($rue89->getContent());

        $this->assertTrue($logHandler->hasWarning('Rue89 extract failed for: 261827'), 'Warning message matched');
    }
}
