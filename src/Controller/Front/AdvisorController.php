<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\User;
use App\Repository\AccountRepository;
use App\Repository\AdvisorAssignmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/conseiller')]
#[IsGranted('ROLE_ADVISOR')]
final class AdvisorController extends AbstractController
{
    #[Route(name: 'app_advisor_index', methods: ['GET'])]
    public function index(AdvisorAssignmentRepository $advisorAssignmentRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('front/advisor/index.html.twig', [
            'assignments' => $advisorAssignmentRepository->findClientsForAdvisor($user),
        ]);
    }

    #[Route('/clients/{id}', name: 'app_advisor_client_show', methods: ['GET'])]
    public function showClient(
        User $id,
        AccountRepository $accountRepository,
        AdvisorAssignmentRepository $advisorAssignmentRepository,
    ): Response {
        $client = $id;

        /** @var User $advisor */
        $advisor = $this->getUser();

        if (!$advisorAssignmentRepository->isAdvisorOf($advisor, $client)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('front/advisor/client_show.html.twig', [
            'client' => $client,
            'accountsWithBalances' => $accountRepository->findAllForUserWithBalances($client),
        ]);
    }
}
