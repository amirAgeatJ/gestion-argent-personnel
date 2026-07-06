<?php

declare(strict_types=1);

namespace App\Controller\Front\Auth;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        MailerInterface $mailer,
    ): Response {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($userRepository->findOneBy(['email' => $user->getEmail()]) !== null) {
                $form->get('email')->addError(new FormError('Cette adresse e-mail est déjà utilisée.'));

                return $this->render('front/auth/register.html.twig', ['form' => $form]);
            }

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPasswordHash($passwordHasher->hashPassword($user, $plainPassword));
            $user->setStatus('active');
            $user->setPreferredCurrency('EUR');

            $entityManager->persist($user);
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Bienvenue sur Gestion Argent Personnel')
                ->htmlTemplate('email/welcome.html.twig')
                ->context(['user' => $user]);
            $mailer->send($email);

            $this->addFlash('success', 'Compte créé avec succès. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/auth/register.html.twig', ['form' => $form]);
    }
}
