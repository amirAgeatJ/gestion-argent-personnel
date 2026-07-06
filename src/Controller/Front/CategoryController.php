<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Category;
use App\Entity\User;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/categories')]
#[IsGranted('ROLE_USER')]
final class CategoryController extends AbstractController
{
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('front/category/index.html.twig', [
            'categories' => $categoryRepository->findAvailableForUser($user),
        ]);
    }

    #[Route('/nouveau', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $category = new Category();
        $category->setOwner($user);

        $form = $this->createForm(CategoryType::class, $category, [
            'availableParents' => $categoryRepository->findAvailableForUser($user),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie créée avec succès.');

            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('front/category/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    #[IsGranted('CATEGORY_EDIT', subject: 'category')]
    public function edit(Category $category, Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(CategoryType::class, $category, [
            'availableParents' => array_filter(
                $categoryRepository->findAvailableForUser($user),
                static fn (Category $c) => $c !== $category,
            ),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie mise à jour avec succès.');

            return $this->redirectToRoute('app_category_index');
        }

        return $this->render('front/category/edit.html.twig', ['form' => $form, 'category' => $category]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    #[IsGranted('CATEGORY_EDIT', subject: 'category')]
    public function delete(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie supprimée.');
        }

        return $this->redirectToRoute('app_category_index');
    }
}
