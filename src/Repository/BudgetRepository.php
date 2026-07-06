<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Budget;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Budget>
 */
class BudgetRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly TransactionRepository $transactionRepository,
    ) {
        parent::__construct($registry, Budget::class);
    }

    /**
     * Budgets d'un utilisateur pour un mois donné, avec le montant déjà dépensé
     * (une seule requête d'agrégation pour tous les budgets, pas une par budget).
     *
     * @return list<array{budget: Budget, spent: string}>
     */
    public function findWithSpentAmountForUser(User $user, \DateTimeImmutable $periodStart): array
    {
        $budgets = $this->createQueryBuilder('b')
            ->addSelect('c')
            ->join('b.category', 'c')
            ->andWhere('b.owner = :user')
            ->andWhere('b.periodStart = :periodStart')
            ->setParameter('user', $user)
            ->setParameter('periodStart', $periodStart)
            ->getQuery()
            ->getResult();

        if ($budgets === []) {
            return [];
        }

        $periodEnd = $periodStart->modify('first day of next month');
        $spentByCategory = $this->transactionRepository->sumExpensesByCategoryForUser($user, $periodStart, $periodEnd);

        return array_map(
            static fn (Budget $budget): array => [
                'budget' => $budget,
                'spent' => $spentByCategory[(string) $budget->getCategory()->getId()] ?? '0.00',
            ],
            $budgets,
        );
    }
}
