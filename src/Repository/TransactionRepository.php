<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<\App\Entity\Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, \App\Entity\Transaction::class);
    }

    /** Transactions d'un compte, avec catégorie et étiquettes préchargées (pas de N+1). */
    public function findForAccountWithCategoryAndTags(Account $account): array
    {
        return $this->createQueryBuilder('t')
            ->addSelect('c', 'tags')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.tags', 'tags')
            ->andWhere('t.account = :account')
            ->setParameter('account', $account)
            ->orderBy('t.occurredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Transactions d'un utilisateur (tous comptes confondus), avec compte, catégorie et
     * étiquettes préchargés en une seule requête, filtrables par type et par catégorie.
     */
    public function findAllForUserWithFilters(User $user, ?string $type = null, ?string $categoryId = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('c', 'a', 'tags')
            ->join('t.account', 'a')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.tags', 'tags')
            ->andWhere('a.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('t.occurredAt', 'DESC');

        if ($type !== null && $type !== '') {
            $qb->andWhere('t.type = :type')->setParameter('type', $type);
        }

        if ($categoryId !== null && $categoryId !== '') {
            $qb->andWhere('c.id = :categoryId')->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Transactions d'un utilisateur (tous comptes confondus) sur une période, avec compte et
     * catégorie préchargés en une seule requête (utilisé par le dashboard : totaux, répartition
     * par catégorie et transactions récentes).
     */
    public function findForUserInPeriod(User $user, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('t')
            ->addSelect('c', 'a')
            ->join('t.account', 'a')
            ->leftJoin('t.category', 'c')
            ->andWhere('a.owner = :user')
            ->andWhere('t.occurredAt >= :start')
            ->andWhere('t.occurredAt < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('t.occurredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Total dépensé par catégorie sur une période, pour un utilisateur (utilisé par les budgets).
     *
     * @return array<string, string> categoryId => somme dépensée
     */
    public function sumExpensesByCategoryForUser(User $user, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.category) AS categoryId')
            ->addSelect('SUM(t.amount) AS total')
            ->join('t.account', 'a')
            ->andWhere('a.owner = :user')
            ->andWhere("t.type = 'expense'")
            ->andWhere('t.occurredAt >= :start')
            ->andWhere('t.occurredAt < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $periodStart)
            ->setParameter('end', $periodEnd)
            ->groupBy('t.category')
            ->getQuery()
            ->getResult();

        return array_column($rows, 'total', 'categoryId');
    }
}
