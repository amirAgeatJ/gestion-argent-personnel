<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Budget;
use App\Entity\User;
use App\Form\BudgetType;
use App\Repository\BudgetRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/budgets')]
#[IsGranted('ROLE_USER')]
final class BudgetController extends AbstractController
{
    #[Route(name: 'app_budget_index', methods: ['GET'])]
    public function index(BudgetRepository $budgetRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $monthStart = new \DateTimeImmutable('first day of this month midnight');

        return $this->render('front/budget/index.html.twig', [
            'budgetsWithSpent' => $budgetRepository->findWithSpentAmountForUser($user, $monthStart),
            'monthStart' => $monthStart,
        ]);
    }

    #[Route('/nouveau', name: 'app_budget_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $budget = new Budget();
        $budget->setOwner($user);
        $budget->setPeriodStart(new \DateTimeImmutable('first day of this month midnight'));

        $form = $this->createForm(BudgetType::class, $budget, [
            'expenseCategories' => $categoryRepository->findAvailableForUser($user, 'expense'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($budget);
            $entityManager->flush();

            $this->addFlash('success', 'Budget créé avec succès.');

            return $this->redirectToRoute('app_budget_index');
        }

        return $this->render('front/budget/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_budget_edit', methods: ['GET', 'POST'])]
    #[IsGranted('OWNERSHIP_EDIT', subject: 'budget')]
    public function edit(Budget $budget, Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(BudgetType::class, $budget, [
            'expenseCategories' => $categoryRepository->findAvailableForUser($user, 'expense'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Budget mis à jour avec succès.');

            return $this->redirectToRoute('app_budget_index');
        }

        return $this->render('front/budget/edit.html.twig', ['form' => $form, 'budget' => $budget]);
    }

    #[Route('/{id}', name: 'app_budget_delete', methods: ['POST'])]
    #[IsGranted('OWNERSHIP_EDIT', subject: 'budget')]
    public function delete(Budget $budget, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$budget->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($budget);
            $entityManager->flush();
            $this->addFlash('success', 'Budget supprimé.');
        }

        return $this->redirectToRoute('app_budget_index');
    }
}
