<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route(name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'roleLabels' => Roles::ASSIGNABLE,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
            'availableRoles' => Roles::ASSIGNABLE,
        ]);
    }

    #[Route('/{id}/statut', name: 'admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre statut.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        if ($this->isCsrfTokenValid('status'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $user->setStatus($user->getStatus() === 'active' ? 'suspended' : 'active');
            $entityManager->flush();
            $this->addFlash('success', 'Statut mis à jour.');
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/role/assign', name: 'admin_user_role_assign', methods: ['POST'])]
    public function assignRole(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $roleCode = $request->getPayload()->getString('roleCode');

        if ($this->isCsrfTokenValid('role_assign'.$user->getId(), $request->getPayload()->getString('_token'))
            && array_key_exists($roleCode, Roles::ASSIGNABLE)
        ) {
            $user->addAssignedRole($roleCode);
            $entityManager->flush();
            $this->addFlash('success', 'Rôle attribué.');
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/role/remove', name: 'admin_user_role_remove', methods: ['POST'])]
    public function removeRole(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $roleCode = $request->getPayload()->getString('roleCode');

        if ($user === $this->getUser() && $roleCode === Roles::ADMIN) {
            $this->addFlash('error', 'Vous ne pouvez pas retirer votre propre rôle administrateur.');

            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        if ($this->isCsrfTokenValid('role_remove'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $user->removeAssignedRole($roleCode);
            $entityManager->flush();
            $this->addFlash('success', 'Rôle retiré.');
        }

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }
}
