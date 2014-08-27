<?php

namespace j0k3r\FeedBundle\Tests\Command;

use j0k3r\FeedBundle\Command\FetchItemsCommand;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class FetchItemsCommandTest extends WebTestCase
{
    private $command;
    private $commandTester;

    public function setUp()
    {
        static::createClient();

        $application = new Application(static::$kernel);
        $application->add(new FetchItemsCommand());

        $this->command = $application->find('feed:fetch-items');
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @see http://symfony.com/doc/current/components/console/helpers/dialoghelper.html#testing-a-command-which-expects-input
     * @param string $input
     */
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    public function testNoParams()
    {
        $this->commandTester->execute(array('command' => $this->command->getName()));

        $this->assertRegExp('`You must add some options to the task : an age or a slug`', $this->commandTester->getDisplay());
    }

    public function testWrongSlug()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '--slug' => 'toto'
        ));

        $this->assertRegExp('`Unable to find Feed document`', $this->commandTester->getDisplay());
    }

    public function testHN()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '--slug' => 'hackernews',
            '-t' => true
        ));

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testNew()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '--age' => 'new',
            '-t' => true
        ));

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }

    public function testOld()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            '--age' => 'old',
            '-t' => true
        ));

        $this->assertRegExp('`items cached.`', $this->commandTester->getDisplay());
    }
}
