<?php

namespace App\Tests\Content;

use App\Content\SiteConfigTest;
use Graby\Extractor\ContentExtractorConfig;
use Graby\Graby;
use Graby\GrabyConfig;
use Graby\SiteConfig\ConfigBuilderConfig;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class SiteConfigTestTest extends TestCase
{
    private const URL = 'https://www.siteconfig-test.example/article';
    private const HTML = '<html><head><title>Config test</title></head><body><div id="original"><p>Original content</p></div><div id="custom"><p>Custom content</p></div></body></html>';
    private const ORIGINAL = "body: //*[@id='original']\nautodetect_on_failure: no\n";
    private const CUSTOM = "body: //*[@id='custom']\nautodetect_on_failure: no\n";

    private string $directory;
    private Filesystem $filesystem;
    private GrabyConfig $config;
    private TestHandler $logs;
    private Logger $logger;
    private SiteConfigTest $tester;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->directory = (getenv('PI_SAFE_TMPDIR') ?: sys_get_temp_dir()) . '/f43-siteconfig-' . bin2hex(random_bytes(16));
        $this->filesystem->mkdir($this->directory . '/configs', 0700);
        $this->filesystem->dumpFile($this->directory . '/configs/siteconfig-test.example.txt', self::ORIGINAL);
        $this->config = new GrabyConfig(
            errorMessage: '',
            extractor: new ContentExtractorConfig(configBuilder: new ConfigBuilderConfig(siteConfig: [$this->directory . '/configs'])),
        );
        $this->logs = new TestHandler();
        $this->logger = new Logger('graby', [$this->logs]);
        $this->tester = new SiteConfigTest($this->config, $this->filesystem, $this->logger, $this->directory . '/tests');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->directory);
    }

    public static function configModes(): iterable
    {
        yield 'append preserves existing rule priority' => [false, 'Original content', 'Custom content'];
        yield 'replace uses custom rules' => [true, 'Custom content', 'Original content'];
    }

    #[DataProvider('configModes')]
    public function testCustomConfigIsAppliedWithoutChangingOriginal(bool $replace, string $expected, string $excluded): void
    {
        $result = $this->tester->run(self::URL, self::CUSTOM, $replace, fn (Graby $graby) => $this->extract($graby));

        $this->assertStringContainsString($expected, $result);
        $this->assertStringNotContainsString($excluded, $result);
        $this->assertSame(self::ORIGINAL, $this->filesystem->readFile($this->directory . '/configs/siteconfig-test.example.txt'));
        $this->assertSame([], glob($this->directory . '/tests/*'));
        $this->assertTrue($this->logs->hasInfoThatContains('found site config'));
        $this->assertTrue($this->logs->hasInfoThatContains('specialized extractors are bypassed'));

        $normal = $this->extract(new Graby($this->config));
        $this->assertStringContainsString('Original content', $normal);
        $this->assertStringNotContainsString('Custom content', $normal);
    }

    #[DataProvider('configModes')]
    public function testWildcardConfigModes(bool $replace, string $expected, string $excluded): void
    {
        $file = $this->directory . '/configs/.wildcard-siteconfig.example.txt';
        $this->filesystem->dumpFile($file, self::ORIGINAL);
        $url = 'https://news.wildcard-siteconfig.example/article';
        $result = $this->tester->run($url, self::CUSTOM, $replace, fn (Graby $graby) => $this->extract($graby, $url));

        $this->assertStringContainsString($expected, $result);
        $this->assertStringNotContainsString($excluded, $result);
        $this->assertSame(self::ORIGINAL, $this->filesystem->readFile($file));
        $this->assertSame([], glob($this->directory . '/tests/*'));
    }

    public function testDomainWithoutExistingConfig(): void
    {
        $result = $this->tester->run('https://new-siteconfig-test.example/article', self::CUSTOM, false, fn (Graby $graby) => $this->extract($graby, 'https://new-siteconfig-test.example/article'));

        $this->assertStringContainsString('Custom content', $result);
        $this->assertStringNotContainsString('Original content', $result);
        $this->assertFileDoesNotExist($this->directory . '/configs/new-siteconfig-test.example.txt');
        $this->assertSame([], glob($this->directory . '/tests/*'));
    }

    public function testAppendedRuleIsUsedWhenExistingRuleDoesNotMatch(): void
    {
        $result = $this->tester->run(self::URL, self::CUSTOM, false, static function (Graby $graby): string {
            $graby->setContentAsPrefetched(str_replace('id="original"', 'id="unmatched"', self::HTML));

            return $graby->fetchContent(self::URL)->getHtml();
        });

        $this->assertStringContainsString('Custom content', $result);
        $this->assertStringNotContainsString('Original content', $result);
    }

    public function testCustomConfigIsScopedToRequestedHost(): void
    {
        $otherUrl = 'https://other-siteconfig-test.example/article';
        $this->filesystem->dumpFile($this->directory . '/configs/other-siteconfig-test.example.txt', self::ORIGINAL);
        $this->tester->run(self::URL, self::CUSTOM, true, function (Graby $graby) use ($otherUrl): void {
            $this->assertStringContainsString('Custom content', $this->extract($graby));
            $result = $this->extract($graby, $otherUrl);
            $this->assertStringContainsString('Original content', $result);
            $this->assertStringNotContainsString('Custom content', $result);
        });
    }

    public function testOverlappingTestsAndTheirCachesAreIsolated(): void
    {
        $this->tester->run(self::URL, self::CUSTOM, true, function (Graby $first): void {
            $this->assertStringContainsString('Custom content', $this->extract($first));

            $this->tester->run(self::URL, self::ORIGINAL, true, function (Graby $second): void {
                $directories = glob($this->directory . '/tests/*');
                $this->assertNotFalse($directories);
                $this->assertCount(2, $directories);
                $this->assertStringContainsString('Original content', $this->extract($second));
                $this->assertStringNotContainsString('Custom content', $this->extract($second));
            });

            $directories = glob($this->directory . '/tests/*');
            $this->assertNotFalse($directories);
            $this->assertCount(1, $directories);
            $this->assertStringContainsString('Custom content', $this->extract($first));
            $this->assertStringNotContainsString('Original content', $this->extract($first));
        });

        $this->assertSame([], glob($this->directory . '/tests/*'));
    }

    public function testTemporaryFilesAreRemovedOnExtractionFailure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Extraction failed');

        try {
            $this->tester->run(self::URL, self::CUSTOM, true, static function (): never {
                throw new \RuntimeException('Extraction failed');
            });
        } finally {
            $this->assertSame([], glob($this->directory . '/tests/*'));
            $this->assertSame(self::ORIGINAL, $this->filesystem->readFile($this->directory . '/configs/siteconfig-test.example.txt'));
        }
    }

    public function testWriteFailureDoesNotRunExtractionAndCleansUp(): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)->onlyMethods(['dumpFile'])->getMock();
        $filesystem->method('dumpFile')->willThrowException(new IOException('Permission denied'));
        $tester = new SiteConfigTest($this->config, $filesystem, $this->logger, $this->directory . '/tests');

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Permission denied');

        try {
            $tester->run(self::URL, self::CUSTOM, true, function (): never {
                $this->fail('Extraction must not run after a write failure.');
            });
        } finally {
            $this->assertSame([], glob($this->directory . '/tests/*'));
        }
    }

    public function testInvalidHostIsRejectedBeforeWritingFiles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('valid siteconfig hostname');

        try {
            $this->tester->run('https://example.com%2Fescaped/article', self::CUSTOM, true, function (): void {
                $this->fail('The parser must not run for an invalid hostname.');
            });
        } finally {
            $this->assertDirectoryDoesNotExist($this->directory . '/tests');
        }
    }

    private function extract(Graby $graby, string $url = self::URL): string
    {
        $graby->setContentAsPrefetched(self::HTML);

        return $graby->fetchContent($url)->getHtml();
    }
}
