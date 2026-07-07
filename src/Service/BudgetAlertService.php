<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Notifier\BudgetExceededNotification;
use App\Repository\BudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Notifier;

class BudgetAlertService
{
    public function __construct(
        private readonly BudgetRepository $budgetRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        #[Autowire(service: 'notifier')]
        private readonly Notifier $notifier,
    ) {
    }

    public function checkAndNotify(User $user, \DateTimeImmutable $periodStart): void
    {
        foreach ($this->budgetRepository->findWithSpentAmountForUser($user, $periodStart) as $entry) {
            $budget = $entry['budget'];
            $spent = $entry['spent'];

            if (bccomp($spent, $budget->getLimitAmount(), 2) < 0) {
                continue;
            }

            $notification = new Notification();
            $notification->setUser($user);
            $notification->setType('budget_exceeded');
            $notification->setMessage(sprintf(
                'Budget "%s" dépassé : %s dépensés sur %s.',
                $budget->getCategory()->getName(),
                $spent,
                $budget->getLimitAmount(),
            ));
            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            $email = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Budget dépassé : '.$budget->getCategory()->getName())
                ->htmlTemplate('email/budget_exceeded.html.twig')
                ->context(['user' => $user, 'budget' => $budget, 'spent' => $spent]);
            $this->mailer->send($email);

            $this->notifier->send(
                new BudgetExceededNotification(
                    sprintf('Budget dépassé pour %s : catégorie "%s"', $user->getEmail(), $budget->getCategory()->getName()),
                    ['email'],
                ),
                ...$this->notifier->getAdminRecipients(),
            );
        }
    }
}
