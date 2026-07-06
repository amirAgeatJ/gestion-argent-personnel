<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * Comptes actifs d'un utilisateur avec leur solde calculé, sans requête N+1
     * (2 requêtes d'agrégation au total, quel que soit le nombre de comptes).
     *
     * @return list<array{account: Account, balance: string}>
     */
    public function findAllForUserWithBalances(User $user): array
    {
        $accounts = $this->createQueryBuilder('a')
            ->andWhere('a.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        if ($accounts === []) {
            return [];
        }

        $outgoing = $this->sumOutgoingByAccount($user);
        $incoming = $this->sumIncomingTransfersByAccount($user);

        return array_map(
            function (Account $account) use ($outgoing, $incoming): array {
                $id = (string) $account->getId();
                $balance = bcadd($outgoing[$id] ?? '0.00', $incoming[$id] ?? '0.00', 2);

                return ['account' => $account, 'balance' => $balance];
            },
            $accounts,
        );
    }

    /** @return array<string, string> accountId => somme (revenus - dépenses - virements sortants) */
    private function sumOutgoingByAccount(User $user): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(t.account) AS accountId')
            ->addSelect("SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) AS total")
            ->from('App\Entity\Transaction', 't')
            ->join('t.account', 'a')
            ->andWhere('a.owner = :user')
            ->setParameter('user', $user)
            ->groupBy('t.account')
            ->getQuery()
            ->getResult();

        return array_column($rows, 'total', 'accountId');
    }

    /** @return array<string, string> accountId => somme des virements reçus */
    private function sumIncomingTransfersByAccount(User $user): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(t.transferToAccount) AS accountId')
            ->addSelect('SUM(COALESCE(t.convertedAmount, t.amount)) AS total')
            ->from('App\Entity\Transaction', 't')
            ->join('t.transferToAccount', 'a')
            ->andWhere('a.owner = :user')
            ->andWhere("t.type = 'transfer'")
            ->setParameter('user', $user)
            ->groupBy('t.transferToAccount')
            ->getQuery()
            ->getResult();

        return array_column($rows, 'total', 'accountId');
    }
}
