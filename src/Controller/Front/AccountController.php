<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Account;
use App\Entity\CheckingAccount;
use App\Entity\CreditCardAccount;
use App\Entity\SavingsAccount;
use App\Entity\User;
use App\Form\CheckingAccountType;
use App\Form\CreditCardAccountType;
use App\Form\SavingsAccountType;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comptes')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    private const array TYPES = [
        'checking' => [CheckingAccount::class, CheckingAccountType::class, 'Compte courant'],
        'savings' => [SavingsAccount::class, SavingsAccountType::class, 'Compte épargne'],
        'credit_card' => [CreditCardAccount::class, CreditCardAccountType::class, 'Carte de crédit'],
    ];

    #[Route(name: 'app_account_index', methods: ['GET'])]
    public function index(AccountRepository $accountRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('front/account/index.html.twig', [
            'accountsWithBalances' => $accountRepository->findAllForUserWithBalances($user),
            'types' => self::TYPES,
        ]);
    }

    #[Route('/nouveau/{type}', name: 'app_account_new', methods: ['GET', 'POST'])]
    public function new(string $type, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!isset(self::TYPES[$type])) {
            throw $this->createNotFoundException('Type de compte inconnu.');
        }

        /** @var User $user */
        $user = $this->getUser();
        [$class, $formClass] = self::TYPES[$type];

        $account = new $class();
        $account->setOwner($user);

        $form = $this->createForm($formClass, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($account);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé avec succès.');

            return $this->redirectToRoute('app_account_index');
        }

        return $this->render('front/account/new.html.twig', [
            'form' => $form,
            'typeLabel' => self::TYPES[$type][2],
        ]);
    }

    #[Route('/{id}', name: 'app_account_show', methods: ['GET'])]
    #[IsGranted('ACCOUNT_VIEW', subject: 'account')]
    public function show(Account $account, TransactionRepository $transactionRepository): Response
    {
        return $this->render('front/account/show.html.twig', [
            'account' => $account,
            'transactions' => $transactionRepository->findForAccountWithCategoryAndTags($account),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_account_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ACCOUNT_EDIT', subject: 'account')]
    public function edit(Account $account, Request $request, EntityManagerInterface $entityManager): Response
    {
        $formClass = match (true) {
            $account instanceof CheckingAccount => CheckingAccountType::class,
            $account instanceof SavingsAccount => SavingsAccountType::class,
            $account instanceof CreditCardAccount => CreditCardAccountType::class,
            default => throw new \LogicException('Type de compte inconnu.'),
        };

        $form = $this->createForm($formClass, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Compte mis à jour avec succès.');

            return $this->redirectToRoute('app_account_show', ['id' => $account->getId()]);
        }

        return $this->render('front/account/edit.html.twig', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_account_delete', methods: ['POST'])]
    #[IsGranted('ACCOUNT_DELETE', subject: 'account')]
    public function delete(Account $account, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$account->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($account);
            $entityManager->flush();
            $this->addFlash('success', 'Compte supprimé.');
        }

        return $this->redirectToRoute('app_account_index');
    }
}
