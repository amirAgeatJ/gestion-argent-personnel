<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /** Catégories système + catégories personnalisées de l'utilisateur, triées pour un select. */
    public function findAvailableForUser(User $user, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.owner = :user OR c.owner IS NULL')
            ->setParameter('user', $user)
            ->orderBy('c.type', 'ASC')
            ->addOrderBy('c.name', 'ASC');

        if ($type !== null) {
            $qb->andWhere('c.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }
}
