<?php

namespace App\Tests\Repository;

use App\Entity\Feed;
use App\Repository\FeedRepository;
use PHPUnit\Framework\TestCase;

class FeedRepositoryTest extends TestCase
{
    public function testEmptyInListReturnsNoFeedsWithoutQuerying(): void
    {
        $repository = $this->getMockBuilder(FeedRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder', 'findAll'])
            ->getMock();
        $repository->expects($this->never())->method('createQueryBuilder');
        $repository->expects($this->never())->method('findAll');

        $this->assertSame([], $repository->findByIds([], 'in'));
    }

    public function testEmptyNotInListReturnsAllFeedsForInitialImport(): void
    {
        $feeds = [new Feed()];
        $repository = $this->getMockBuilder(FeedRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder', 'findAll'])
            ->getMock();
        $repository->expects($this->never())->method('createQueryBuilder');
        $repository->expects($this->once())->method('findAll')->willReturn($feeds);

        $this->assertSame($feeds, $repository->findByIds([], 'notIn'));
    }
}
