<?php

namespace Tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\Github;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class GithubTest extends \PHPUnit_Framework_TestCase
{
    public function dataMatch()
    {
        return [
            ['https://github.com/j0k3r/f43.me', true],
            ['http://github.com/symfony/symfony', true],
            ['https://github.com/pomm-project/ModelManager', true],
            ['https://github.com/Strider-CD/strider', true],
            ['https://github.com/phpcr/phpcr.github.io/', true],
            ['https://gitlab.com/gitlab', false],
            ['https://github.com/alebcay/awesome-shell/blob/master/README.md', false],
            ['https://github.com/msporny/dna/pull/1', true],
            ['https://github.com/octocat/Hello-World/issues/212', true],
            ['http://user@:80', false],
        ];
    }

    /**
     * @dataProvider dataMatch
     */
    public function testMatch($url, $expected)
    {
        $github = new Github('client_id', 'client_secret');
        $this->assertEquals($expected, $github->match($url));
    }

    public function testContent()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory('<div>README</div>')),
            new Response(200, []),
            new Response(400, [], Stream::factory('oops')),
        ]);

        $client->getEmitter()->attach($mock);

        $github = new Github('client_id', 'client_secret');
        $github->setClient($client);

        // first test fail because we didn't match an url, so GithubId isn't defined
        $this->assertEmpty($github->getContent());

        $github->match('http://www.github.com/photos/palnick');

        // consecutive calls
        $this->assertEquals('<div>README</div>', $github->getContent());
        // this one will got an empty array
        $this->assertEmpty($github->getContent());
        // this one will catch an exception
        $this->assertEmpty($github->getContent());
    }

    public function testIssue()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([
                'html_url' => 'http://1.1.1.1',
                'title' => 'test',
                'comments' => 0,
                'created_at' => '2015-08-04T13:49:04Z',
                'body_html' => 'body',
                'user' => ['html_url' => 'http://2.2.2.2', 'login' => 'login'],
            ]))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $github = new Github('client_id', 'client_secret');
        $github->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $github->setLogger($logger);

        // first test fail because we didn't match an url, so GithubId isn't defined
        $this->assertEmpty($github->getContent());

        $github->match('https://github.com/octocat/Hello-World/issues/212');

        // consecutive calls
        $this->assertEquals('<div><em>Issue on Github</em><h2><a href="http://1.1.1.1">test</a></h2><ul><li>by <a href="http://2.2.2.2">login</a></li><li>on 04/08/2015</li><li>0 comments</li></ul></ul>body</div>', $github->getContent());
        // this one will catch an exception
        $this->assertEmpty($github->getContent());
    }

    public function testPR()
    {
        $client = new Client();

        $mock = new Mock([
            new Response(200, [], Stream::factory(json_encode([
                'base' => ['description' => 'test', 'repo' => ['html_url' => 'http://0.0.0.0', 'full_name' => 'name', 'description' => 'desc']],
                'html_url' => 'http://1.1.1.1',
                'title' => 'test',
                'commits' => 0,
                'comments' => 0,
                'created_at' => '2015-08-04T13:49:04Z',
                'body_html' => 'body',
                'user' => ['html_url' => 'http://2.2.2.2', 'login' => 'login'], ]))),
            new Response(400, [], Stream::factory(json_encode('oops'))),
        ]);

        $client->getEmitter()->attach($mock);

        $github = new Github('client_id', 'client_secret');
        $github->setClient($client);

        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $github->setLogger($logger);

        // first test fail because we didn't match an url, so GithubId isn't defined
        $this->assertEmpty($github->getContent());

        $github->match('https://github.com/msporny/dna/pull/1');

        // consecutive calls
        $this->assertEquals('<div><em>Pull request on Github</em><h2><a href="http://0.0.0.0">name</a></h2><p>desc</p><h3>PR: <a href="http://1.1.1.1">test</a></h3><ul><li>by <a href="http://2.2.2.2">login</a></li><li>on 04/08/2015</li><li>0 commits</li><li>0 comments</li></ul>body</div>', $github->getContent());
        // this one will catch an exception
        $this->assertEmpty($github->getContent());
    }
}
