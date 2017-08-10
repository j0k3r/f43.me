<?php

namespace Tests\FeedBundle\Command;

use Api43\FeedBundle\Command\FetchItemsCommand;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class FetchItemsCommandTest extends WebTestCase
{
    private $command;
    private $commandTester;

    public function setUp()
    {
        $client = static::createClient();

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand());

        $this->command = $application->find('feed:fetch-items');
        $this->commandTester = new CommandTester($this->command);

        $simplePieItem = $this->getMockBuilder('SimplePie_Item')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieItem->expects($this->any())
            ->method('get_description')
            ->will($this->returnValue('desc'));

        $simplePieItem->expects($this->any())
            ->method('get_permalink')
            ->will($this->returnValue('http://localhost'));

        $simplePie = $this->getMockBuilder('SimplePie')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePie->expects($this->any())
            ->method('get_items')
            ->will($this->returnValue([$simplePieItem]));

        $simplePie->expects($this->any())
            ->method('get_description')
            ->will($this->returnValue('desc'));

        $simplePieProxy = $this->getMockBuilder('Api43\FeedBundle\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieProxy->expects($this->any())
            ->method('setUrl')
            ->will($this->returnSelf());

        $simplePieProxy->expects($this->any())
            ->method('init')
            ->will($this->returnValue($simplePie));

        $client->getContainer()->set('simple_pie_proxy', $simplePieProxy);

        $logger = new Logger('import');
        $this->handler = new TestHandler();
        $logger->pushHandler($this->handler);

        $client->getContainer()->set('monolog.logger.import', $logger);
    }

    public function testNoParams()
    {
        $this->commandTester->execute(['command' => $this->command->getName()]);

        $this->assertRegExp('`You must add some options to the task : an age or a slug`', $this->commandTester->getDisplay());
    }

    public function testWrongSlug()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--slug' => 'toto',
        ]);

        $this->assertRegExp('`Unable to find Feed document`', $this->commandTester->getDisplay());
    }

    public function testHN()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--slug' => 'hackernews',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertContains('Working on', $records[0]['message']);
        $this->assertContains('HackerNews', $records[0]['message']);

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testNew()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--age' => 'new',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertContains('Working on', $records[0]['message']);

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testOld()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--age' => 'old',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $records = $this->handler->getRecords();

        $this->assertGreaterThan(0, $records);
        $this->assertContains('Working on', $records[0]['message']);

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }

    /**
     * @see http://symfony.com/doc/current/components/console/helpers/dialoghelper.html#testing-a-command-which-expects-input
     *
     * @param string $input
     */
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }
}
