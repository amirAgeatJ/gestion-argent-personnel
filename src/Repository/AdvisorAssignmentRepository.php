<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AdvisorAssignment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdvisorAssignment>
 */
class AdvisorAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdvisorAssignment::class);
    }

    public function isAdvisorOf(User $advisor, User $client): bool
    {
        $count = $this->createQueryBuilder('aa')
            ->select('COUNT(aa.id)')
            ->andWhere('aa.advisor = :advisor')
            ->andWhere('aa.client = :client')
            ->setParameter('advisor', $advisor)
            ->setParameter('client', $client)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function findClientsForAdvisor(User $advisor): array
    {
        return $this->createQueryBuilder('aa')
            ->addSelect('c')
            ->join('aa.client', 'c')
            ->andWhere('aa.advisor = :advisor')
            ->setParameter('advisor', $advisor)
            ->orderBy('c.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
