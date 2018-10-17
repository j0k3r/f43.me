<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\FetchItemsCommand;
use AppBundle\Content\Import;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group legacy
 *
 * Because there is no right way to override existing service in the upcoming Symfony 4.0
 * without modifiy them globally (using config_test.yml)
 */
class FetchItemsCommandTest extends WebTestCase
{
    private $command;
    private $commandTester;

    public function setUp()
    {
        $client = static::createClient();

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

        $simplePieProxy = $this->getMockBuilder('AppBundle\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePieProxy->expects($this->any())
            ->method('setUrl')
            ->will($this->returnSelf());

        $simplePieProxy->expects($this->any())
            ->method('init')
            ->will($this->returnValue($simplePie));

        $logger = new Logger('import');
        $this->handler = new TestHandler();
        $logger->pushHandler($this->handler);

        self::$kernel->getContainer()->get('app.parser.chain.test')->addParser(
            self::$kernel->getContainer()->get('app.parser.internal.test'),
            'internal'
        );

        self::$kernel->getContainer()->get('app.parser.chain.test')->addParser(
            self::$kernel->getContainer()->get('app.parser.external.test'),
            'external'
        );

        self::$kernel->getContainer()->get('app.improver.chain.test')->addImprover(
            self::$kernel->getContainer()->get('app.improver.default_improver.test'),
            'default_improver'
        );

        self::$kernel->getContainer()->get('app.improver.chain.test')->addImprover(
            self::$kernel->getContainer()->get('app.improver.hackernews.test'),
            'hackernews'
        );

        $import = new Import(
            $simplePieProxy,
            self::$kernel->getContainer()->get('app.content.extractor.test'),
            self::$kernel->getContainer()->get('event_dispatcher.test'),
            self::$kernel->getContainer()->get('dm.test'),
            $logger
        );

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand(
            self::$kernel->getContainer()->get('app.repository.feed.test'),
            self::$kernel->getContainer()->get('app.repository.feed_item.test'),
            // self::$kernel->getContainer()->get('app.content.import.test'),
            $import,
            self::$kernel->getContainer()->get('router.test'),
            'f43.me'
        ));

        $this->command = $application->find('feed:fetch-items');
        $this->commandTester = new CommandTester($this->command);
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
        $stream = fopen('php://memory', 'r+b', false);
        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }
}
