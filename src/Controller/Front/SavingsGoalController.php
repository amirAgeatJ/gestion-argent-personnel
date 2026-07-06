<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\SavingsAccount;
use App\Entity\SavingsGoal;
use App\Entity\User;
use App\Form\SavingsGoalType;
use App\Repository\AccountRepository;
use App\Repository\SavingsGoalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/objectifs')]
#[IsGranted('ROLE_USER')]
final class SavingsGoalController extends AbstractController
{
    #[Route(name: 'app_savings_goal_index', methods: ['GET'])]
    public function index(SavingsGoalRepository $savingsGoalRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('front/savings_goal/index.html.twig', [
            'goals' => $savingsGoalRepository->findBy(['owner' => $user]),
        ]);
    }

    #[Route('/nouveau', name: 'app_savings_goal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AccountRepository $accountRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $goal = new SavingsGoal();
        $goal->setOwner($user);

        $form = $this->createForm(SavingsGoalType::class, $goal, [
            'savingsAccounts' => array_filter(
                $accountRepository->findBy(['owner' => $user]),
                static fn ($account) => $account instanceof SavingsAccount,
            ),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($goal);
            $entityManager->flush();

            $this->addFlash('success', 'Objectif d\'épargne créé avec succès.');

            return $this->redirectToRoute('app_savings_goal_index');
        }

        return $this->render('front/savings_goal/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_savings_goal_edit', methods: ['GET', 'POST'])]
    #[IsGranted('OWNERSHIP_EDIT', subject: 'goal')]
    public function edit(SavingsGoal $goal, Request $request, EntityManagerInterface $entityManager, AccountRepository $accountRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(SavingsGoalType::class, $goal, [
            'savingsAccounts' => array_filter(
                $accountRepository->findBy(['owner' => $user]),
                static fn ($account) => $account instanceof SavingsAccount,
            ),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Objectif mis à jour avec succès.');

            return $this->redirectToRoute('app_savings_goal_index');
        }

        return $this->render('front/savings_goal/edit.html.twig', ['form' => $form, 'goal' => $goal]);
    }

    #[Route('/{id}', name: 'app_savings_goal_delete', methods: ['POST'])]
    #[IsGranted('OWNERSHIP_EDIT', subject: 'goal')]
    public function delete(SavingsGoal $goal, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$goal->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($goal);
            $entityManager->flush();
            $this->addFlash('success', 'Objectif supprimé.');
        }

        return $this->redirectToRoute('app_savings_goal_index');
    }
}
