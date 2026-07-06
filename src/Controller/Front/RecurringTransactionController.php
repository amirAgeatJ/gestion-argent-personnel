<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\RecurringTransaction;
use App\Entity\User;
use App\Form\RecurringTransactionType;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Repository\RecurringTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transactions-recurrentes')]
#[IsGranted('ROLE_USER')]
final class RecurringTransactionController extends AbstractController
{
    #[Route(name: 'app_recurring_transaction_index', methods: ['GET'])]
    public function index(RecurringTransactionRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('front/recurring_transaction/index.html.twig', [
            'recurringTransactions' => $repository->findAllForUser($user),
        ]);
    }

    #[Route('/nouveau', name: 'app_recurring_transaction_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AccountRepository $accountRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $recurring = new RecurringTransaction();

        $form = $this->createForm(RecurringTransactionType::class, $recurring, [
            'accounts' => $accountRepository->findBy(['owner' => $user]),
            'categories' => $categoryRepository->findAvailableForUser($user),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recurring);
            $entityManager->flush();

            $this->addFlash('success', 'Transaction récurrente créée avec succès.');

            return $this->redirectToRoute('app_recurring_transaction_index');
        }

        return $this->render('front/recurring_transaction/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_recurring_transaction_edit', methods: ['GET', 'POST'])]
    #[IsGranted('OWNERSHIP_EDIT', subject: 'recurringTransaction')]
    public function edit(
        RecurringTransaction $recurringTransaction,
        Request $request,
        EntityManagerInterface $entityManager,
        AccountRepository $accountRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(RecurringTransactionType::class, $recurringTransaction, [
            'accounts' => $accountRepository->findBy(['owner' => $user]),
            'categories' => $categoryRepository->findAvailableForUser($user),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Transaction récurrente mise à jour.');

            return $this->redirectToRoute('app_recurring_transaction_index');
        }

        return $this->render('front/recurring_transaction/edit.html.twig', [
            'form' => $form,
            'recurringTransaction' => $recurringTransaction,
        ]);
    }

    #[Route('/{id}', name: 'app_recurring_transaction_delete', methods: ['POST'])]
    #[IsGranted('OWNERSHIP_EDIT', subject: 'recurringTransaction')]
    public function delete(RecurringTransaction $recurringTransaction, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recurringTransaction->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($recurringTransaction);
            $entityManager->flush();
            $this->addFlash('success', 'Transaction récurrente supprimée.');
        }

        return $this->redirectToRoute('app_recurring_transaction_index');
    }
}
