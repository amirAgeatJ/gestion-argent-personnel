<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Transaction;
use App\Entity\User;
use App\Form\TransactionType;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Repository\TransactionRepository;
use App\Service\BudgetAlertService;
use App\Service\TransferService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transactions')]
#[IsGranted('ROLE_USER')]
final class TransactionController extends AbstractController
{
    #[Route(name: 'app_transaction_index', methods: ['GET'])]
    public function index(Request $request, TransactionRepository $transactionRepository, CategoryRepository $categoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $type = $request->query->getString('type');
        $categoryId = $request->query->getString('category');

        return $this->render('front/transaction/index.html.twig', [
            'transactions' => $transactionRepository->findAllForUserWithFilters($user, $type, $categoryId),
            'categories' => $categoryRepository->findAvailableForUser($user),
            'selectedType' => $type,
            'selectedCategory' => $categoryId,
        ]);
    }

    /**
     * Alimente dynamiquement le select "catégorie" en fonction du type choisi par l'utilisateur
     * (revenu/dépense), sans recharger la page — complète le filtrage serveur fait dans
     * TransactionType via les Form Events (PRE_SET_DATA/PRE_SUBMIT), qui reste la source de vérité.
     */
    #[Route('/categories.json', name: 'app_transaction_categories', methods: ['GET'])]
    public function categories(Request $request, CategoryRepository $categoryRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $type = $request->query->getString('type');

        $categories = $type !== '' && $type !== 'transfer'
            ? $categoryRepository->findAvailableForUser($user, $type)
            : [];

        return new JsonResponse(array_map(
            static fn ($category) => ['id' => (string) $category->getId(), 'name' => $category->getName()],
            $categories,
        ));
    }

    #[Route('/nouveau', name: 'app_transaction_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        AccountRepository $accountRepository,
        TagRepository $tagRepository,
        TransferService $transferService,
        BudgetAlertService $budgetAlertService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $accounts = $accountRepository->findBy(['owner' => $user]);

        if ($accounts === []) {
            $this->addFlash('error', 'Vous devez créer un compte avant d\'ajouter une transaction.');

            return $this->redirectToRoute('app_account_new', ['type' => 'checking']);
        }

        $transaction = new Transaction();
        $transaction->setOccurredAt(new \DateTimeImmutable());

        $form = $this->createForm(TransactionType::class, $transaction, [
            'user' => $user,
            'accounts' => $accounts,
            'tags' => $tagRepository->findBy(['owner' => $user]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $transferService->prepareTransfer($transaction);

            $entityManager->persist($transaction);
            $entityManager->flush();

            if ($transaction->getType() === 'expense') {
                $budgetAlertService->checkAndNotify($user, new \DateTimeImmutable('first day of this month midnight'));
            }

            $this->addFlash('success', 'Transaction enregistrée avec succès.');

            return $this->redirectToRoute('app_transaction_index');
        }

        return $this->render('front/transaction/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_transaction_edit', methods: ['GET', 'POST'])]
    public function edit(
        Transaction $transaction,
        Request $request,
        EntityManagerInterface $entityManager,
        AccountRepository $accountRepository,
        TagRepository $tagRepository,
        TransferService $transferService,
    ): Response {
        $this->denyAccessUnlessGranted('ACCOUNT_EDIT', $transaction->getAccount());

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(TransactionType::class, $transaction, [
            'user' => $user,
            'accounts' => $accountRepository->findBy(['owner' => $user]),
            'tags' => $tagRepository->findBy(['owner' => $user]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $transferService->prepareTransfer($transaction);
            $entityManager->flush();

            $this->addFlash('success', 'Transaction mise à jour avec succès.');

            return $this->redirectToRoute('app_transaction_index');
        }

        return $this->render('front/transaction/edit.html.twig', [
            'form' => $form,
            'transaction' => $transaction,
        ]);
    }

    #[Route('/{id}', name: 'app_transaction_delete', methods: ['POST'])]
    public function delete(Transaction $transaction, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ACCOUNT_EDIT', $transaction->getAccount());

        if ($this->isCsrfTokenValid('delete'.$transaction->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($transaction);
            $entityManager->flush();
            $this->addFlash('success', 'Transaction supprimée.');
        }

        return $this->redirectToRoute('app_transaction_index');
    }
}
