<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AdvisorAssignment;
use App\Form\AdvisorAssignmentType;
use App\Repository\AdvisorAssignmentRepository;
use App\Repository\UserRepository;
use App\Security\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/conseillers')]
#[IsGranted('ROLE_ADMIN')]
final class AdvisorAssignmentController extends AbstractController
{
    #[Route(name: 'admin_advisor_assignment_index', methods: ['GET'])]
    public function index(AdvisorAssignmentRepository $advisorAssignmentRepository): Response
    {
        return $this->render('admin/advisor_assignment/index.html.twig', [
            'assignments' => $advisorAssignmentRepository->findBy([], ['assignedAt' => 'DESC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_advisor_assignment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $assignment = new AdvisorAssignment();

        $advisors = array_values(array_filter(
            $userRepository->findAll(),
            static fn ($u) => $u->hasAssignedRole(Roles::ADVISOR) || $u->hasAssignedRole(Roles::ADMIN),
        ));

        $form = $this->createForm(AdvisorAssignmentType::class, $assignment, [
            'advisors' => $advisors,
            'clients' => $userRepository->findAll(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($assignment);
            $entityManager->flush();

            $this->addFlash('success', 'Assignation créée avec succès.');

            return $this->redirectToRoute('admin_advisor_assignment_index');
        }

        return $this->render('admin/advisor_assignment/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'admin_advisor_assignment_delete', methods: ['POST'])]
    public function delete(AdvisorAssignment $assignment, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$assignment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($assignment);
            $entityManager->flush();
            $this->addFlash('success', 'Assignation supprimée.');
        }

        return $this->redirectToRoute('admin_advisor_assignment_index');
    }
}
