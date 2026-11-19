<?php

namespace App\Tests\Form\Type;

use App\Entity\Feed;
use App\Form\Type\FeedType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class FeedTypeTest extends FormIntegrationTestCase
{
    public static function parserSettings(): iterable
    {
        yield 'new feed' => [null];
        yield 'legacy external feed' => ['external'];
    }

    #[DataProvider('parserSettings')]
    public function testEditingDoesNotRequireAParserChoice(?string $legacyParser): void
    {
        $feed = new Feed();
        if (null !== $legacyParser) {
            $feed->setParser($legacyParser);
        }
        $form = $this->factory->create(FeedType::class, $feed);
        $this->assertFalse($form->has('parser'));

        $form->submit([
            'name' => 'Edited feed',
            'host' => 'feed.example',
            'link' => 'https://feed.example/rss.xml',
            'formatter' => 'rss',
            'sort_by' => 'published_at',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame('Edited feed', $feed->getName());
        $this->assertSame($legacyParser ?? 'internal', $feed->getParser());
    }
}
