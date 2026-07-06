<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExchangeRate>
 */
class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    public function findFreshOrNull(string $base, string $target, \DateTimeImmutable $now, int $maxAgeHours = 24): ?ExchangeRate
    {
        $rate = $this->findOneBy(['baseCurrency' => $base, 'targetCurrency' => $target]);

        if ($rate !== null && $rate->isFreshAsOf($now, $maxAgeHours)) {
            return $rate;
        }

        return null;
    }
}
