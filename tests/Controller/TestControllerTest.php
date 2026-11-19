<?php

namespace App\Tests\Controller;

use App\Content\SiteConfigTest;
use Graby\Graby;
use Graby\GrabyConfig;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class TestControllerTest extends FeedWebTestCase
{
    private const URL = 'https://siteconfig-controller.example/article';
    private const HTML = '<html><head><title>Test page</title></head><body><article><p>Original article content for testing extraction without external HTTP requests.</p><p id="custom">Custom siteconfig marker</p></article></body></html>';

    private ?string $temporaryDirectory = null;

    protected function tearDown(): void
    {
        if (null !== $this->temporaryDirectory) {
            (new Filesystem())->remove($this->temporaryDirectory);
        }

        parent::tearDown();
    }

    public function testFeedTest(): void
    {
        $client = static::getAuthorizedClient();

        $crawler = $client->request('GET', '/feed/test');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('input[type=url]'));
        $this->assertCount(1, $crawler->filter('input[type=hidden]'));
        $this->assertCount(0, $crawler->filter('select'));
        $this->assertCount(1, $crawler->filter('button[type=submit]'));
        $this->assertCount(1, $crawler->filter('#siteconfig > small'));
    }

    public function testFeedTestSubmit(): void
    {
        $client = static::getAuthorizedClient();
        $client->disableReboot();
        static::getContainer()->get('graby')->setContentAsPrefetched(str_replace('Original article content', str_repeat('Original article content. ', 30), self::HTML));

        $crawler = $client->request('GET', '/feed/test');
        $form = $crawler->filter('form.custom button[type=submit]')->form();
        $crawler = $client->submit($form, [
            'item_test[link]' => self::URL,
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('details > div > ul'));
        $this->assertStringContainsString('Original article content', $crawler->filter('details[open]')->text());
    }

    public function testFeedTestSubmitWithSiteConfig(): void
    {
        $this->assertCustomConfigExtraction('https://www.lemonde.fr/article', true);
    }

    public function testFeedTestSubmitWithSiteConfigNonExistent(): void
    {
        $this->assertCustomConfigExtraction(self::URL, false);
    }

    public function testTemporaryFileFailureIsDisplayed(): void
    {
        $client = static::getAuthorizedClient();
        $client->disableReboot();
        $tester = $this->createMock(SiteConfigTest::class);
        $tester->method('run')->willThrowException(new IOException('Permission denied'));
        static::getContainer()->set(SiteConfigTest::class, $tester);

        $crawler = $client->request('GET', '/feed/test');
        $client->submit($crawler->filter('form.custom button[type=submit]')->form(), [
            'item_test[link]' => self::URL,
            'item_test[siteconfig]' => 'body: //body',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('form.custom', 'Unable to test the siteconfig: Permission denied');
        $this->assertSelectorNotExists('details[open]');
    }

    private function assertCustomConfigExtraction(string $url, bool $replace): void
    {
        $client = static::getAuthorizedClient();
        $this->provideTestPage($client);
        $crawler = $client->request('GET', '/feed/test');
        $crawler = $client->submit($crawler->filter('form.custom button[type=submit]')->form(), [
            'item_test[link]' => $url,
            'item_test[siteconfig]' => "body: //*[@id='custom']\nautodetect_on_failure: no",
            'item_test[siteconfig_replace]' => $replace,
        ]);

        $this->assertResponseIsSuccessful();
        $result = $crawler->filter('details[open]')->text();
        $this->assertStringContainsString('Custom siteconfig marker', $result);
        $this->assertStringNotContainsString('Original article content', $result);
        $this->assertStringContainsString('found site config', $crawler->filter('details:not([open])')->text());
    }

    private function provideTestPage(KernelBrowser $client): void
    {
        $client->disableReboot();
        $this->temporaryDirectory = (getenv('PI_SAFE_TMPDIR') ?: sys_get_temp_dir()) . '/f43-controller-siteconfig-' . bin2hex(random_bytes(16));
        $realTester = new SiteConfigTest(
            static::getContainer()->get(GrabyConfig::class),
            new Filesystem(),
            static::getContainer()->get('monolog.logger.graby'),
            $this->temporaryDirectory,
        );
        $tester = $this->createMock(SiteConfigTest::class);
        $tester->method('run')->willReturnCallback(static fn (string $url, string $config, bool $replace, callable $test) => $realTester->run(
            $url,
            $config,
            $replace,
            static function (Graby $graby) use ($test) {
                $graby->setContentAsPrefetched(self::HTML);

                return $test($graby);
            },
        ));
        static::getContainer()->set(SiteConfigTest::class, $tester);
    }
}
