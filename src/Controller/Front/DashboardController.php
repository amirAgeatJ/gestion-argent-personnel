<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\AccountRepository;
use App\Repository\BudgetRepository;
use App\Repository\NotificationRepository;
use App\Repository\TransactionRepository;
use App\Service\DisplayCurrencyProvider;
use App\Service\ExchangeRateProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    private const array CHART_COLORS = ['#7662ea', '#f59e0b', '#15803d', '#0891b2', '#db2777', '#64748b', '#b91c1c', '#7c3aed'];

    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        AccountRepository $accountRepository,
        BudgetRepository $budgetRepository,
        NotificationRepository $notificationRepository,
        TransactionRepository $transactionRepository,
        DisplayCurrencyProvider $displayCurrencyProvider,
        ExchangeRateProvider $exchangeRateProvider,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $monthStart = new \DateTimeImmutable('first day of this month midnight');
        $monthEnd = $monthStart->modify('first day of next month');
        $displayCurrency = $displayCurrencyProvider->getCurrency();

        $accountsWithBalances = $accountRepository->findAllForUserWithBalances($user);
        $monthlyTransactions = $transactionRepository->findForUserInPeriod($user, $monthStart, $monthEnd);

        $totalBalance = '0';
        foreach ($accountsWithBalances as $entry) {
            $totalBalance = bcadd(
                $totalBalance,
                $exchangeRateProvider->convert($entry['balance'], $entry['account']->getCurrency(), $displayCurrency),
                2,
            );
        }

        $totalExpenses = '0';
        $totalIncome = '0';
        $expensesByCategory = [];

        foreach ($monthlyTransactions as $transaction) {
            /** @var Transaction $transaction */
            $converted = $exchangeRateProvider->convert($transaction->getAmount(), $transaction->getCurrency(), $displayCurrency);

            if ($transaction->getType() === 'expense') {
                $totalExpenses = bcadd($totalExpenses, $converted, 2);
                $categoryName = $transaction->getCategory()?->getName() ?? 'Autre';
                $expensesByCategory[$categoryName] = bcadd($expensesByCategory[$categoryName] ?? '0', $converted, 2);
            } elseif ($transaction->getType() === 'income') {
                $totalIncome = bcadd($totalIncome, $converted, 2);
            }
        }

        $chartSlices = [];
        $colorIndex = 0;
        foreach ($expensesByCategory as $label => $value) {
            $chartSlices[] = [
                'label' => $label,
                'value' => (float) $value,
                'color' => self::CHART_COLORS[$colorIndex % count(self::CHART_COLORS)],
            ];
            ++$colorIndex;
        }

        return $this->render('front/dashboard/index.html.twig', [
            'displayCurrency' => $displayCurrency,
            'totalBalance' => $totalBalance,
            'totalExpenses' => $totalExpenses,
            'totalIncome' => $totalIncome,
            'chartSlices' => $chartSlices,
            'accounts' => $accountsWithBalances,
            'budgets' => $budgetRepository->findWithSpentAmountForUser($user, $monthStart),
            'recentTransactions' => array_slice($monthlyTransactions, 0, 5),
            'unreadNotifications' => $notificationRepository->countUnreadForUser($user),
        ]);
    }
}
