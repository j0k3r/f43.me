<?php

namespace j0k3r\FeedBundle\Tests\Command;

use j0k3r\FeedBundle\Command\RemoveItemsCommand;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveItemsCommandTest extends WebTestCase
{
    private $command;
    private $commandTester;

    public function setUp()
    {
        $this->client = static::createClient();

        $application = new Application(static::$kernel);
        $application->add(new RemoveItemsCommand());

        $this->command = $application->find('feed:remove-items');
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @see http://symfony.com/doc/current/components/console/helpers/dialoghelper.html#testing-a-command-which-expects-input
     */
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    public function testRemoveAllAvailable()
    {
        $this->commandTester->execute(array('command' => $this->command->getName()));

        $this->assertRegExp('`0 items removed.`', $this->commandTester->getDisplay());
    }

    public function testRemoveForOneFeed()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'hackernews',
        ));

        $this->assertRegExp('`0 items removed.`', $this->commandTester->getDisplay());
    }

    public function testRemoveBadSlug()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'toto',
        ));

        $this->assertRegExp('`Unable to find Feed document`', $this->commandTester->getDisplay());
    }

    public function testRemoveAllMaxYes()
    {
        $dialog = $this->command->getHelper('dialog');
        $dialog->setInputStream($this->getInputStream("yes\n"));

        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'toto',
            '--max' => 0,
        ));

        $this->assertRegExp('`You will remove ALL items, are your sure?`', $this->commandTester->getDisplay());
    }

    public function testRemoveAllMaxNo()
    {
        $dialog = $this->command->getHelper('dialog');
        $dialog->setInputStream($this->getInputStream("no\n"));

        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'toto',
            '--max' => 0,
        ));

        $this->assertRegExp('`remove everything from your database, pfiou`', $this->commandTester->getDisplay());
    }

    public function testRemove()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '-t' => true,
            '--slug' => 'hackernews',
            '--max' => 2,
        ));

        $this->assertRegExp('`items removed.`', $this->commandTester->getDisplay());
    }
}
