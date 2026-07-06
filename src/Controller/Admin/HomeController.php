<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\AccountRepository;
use App\Repository\AuditLogRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class HomeController extends AbstractController
{
    #[Route(name: 'admin_home', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        AccountRepository $accountRepository,
        TransactionRepository $transactionRepository,
        AuditLogRepository $auditLogRepository,
    ): Response {
        return $this->render('admin/home/index.html.twig', [
            'userCount' => $userRepository->count([]),
            'accountCount' => $accountRepository->count([]),
            'transactionCount' => $transactionRepository->count([]),
            'recentAuditLogs' => $auditLogRepository->findRecent(20),
        ]);
    }
}
