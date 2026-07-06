<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RecurringTransaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecurringTransaction>
 */
class RecurringTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringTransaction::class);
    }

    /** @return list<RecurringTransaction> */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('a', 'c')
            ->join('r.account', 'a')
            ->leftJoin('r.category', 'c')
            ->andWhere('a.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('r.nextRunDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<RecurringTransaction> */
    public function findDueForProcessing(\DateTimeImmutable $asOf): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('a', 'c')
            ->join('r.account', 'a')
            ->leftJoin('r.category', 'c')
            ->andWhere('r.active = true')
            ->andWhere('r.nextRunDate <= :asOf')
            ->setParameter('asOf', $asOf)
            ->getQuery()
            ->getResult();
    }
}
