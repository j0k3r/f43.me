<?php

namespace App\Tests\Repository;

use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LogRepositoryTest extends KernelTestCase
{
    public function testStatsAreGroupedByDayBeforeLimitingResults(): void
    {
        $container = self::getContainer();
        $connection = $container->get(EntityManagerInterface::class)->getConnection();
        $repository = $container->get(LogRepository::class);

        $connection->beginTransaction();

        try {
            $connection->executeStatement('DELETE FROM log');
            $this->assertSame([], $repository->findStatsForLastDays());

            foreach ([
                ['2025-12-31 12:00:00', 50],
                ['2026-01-01 18:00:00', 4],
                ['2026-01-01 08:00:00', 3],
            ] as [$date, $items]) {
                $connection->insert('log', ['created_at' => $date, 'items_number' => $items]);
            }

            for ($hour = 0; $hour < 21; ++$hour) {
                $connection->insert('log', [
                    'created_at' => \sprintf('2026-01-02 %02d:00:00', $hour),
                    'items_number' => 2,
                ]);
            }

            $this->assertSame([
                '31/12/2025' => 50,
                '01/01/2026' => 7,
                '02/01/2026' => 42,
            ], $repository->findStatsForLastDays());

            $this->assertSame([
                '01/01/2026' => 7,
                '02/01/2026' => 42,
            ], $repository->findStatsForLastDays(2));
        } finally {
            $connection->rollBack();
        }
    }
}
