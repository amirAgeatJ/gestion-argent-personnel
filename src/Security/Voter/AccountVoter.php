<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Account;
use App\Entity\User;
use App\Repository\AdvisorAssignmentRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Un compte n'est visible/modifiable que par son propriétaire (tout droit), par un
 * conseiller qui lui est explicitement assigné (lecture seule), ou par un administrateur.
 */
final class AccountVoter extends Voter
{
    public const string VIEW = 'ACCOUNT_VIEW';
    public const string EDIT = 'ACCOUNT_EDIT';
    public const string DELETE = 'ACCOUNT_DELETE';

    public function __construct(
        private readonly AdvisorAssignmentRepository $advisorAssignmentRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true) && $subject instanceof Account;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$subject instanceof Account) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if ($subject->getOwner() === $user) {
            return true;
        }

        if ($attribute === self::VIEW
            && in_array('ROLE_ADVISOR', $user->getRoles(), true)
            && $subject->getOwner() !== null
            && $this->advisorAssignmentRepository->isAdvisorOf($user, $subject->getOwner())
        ) {
            return true;
        }

        return false;
    }
}
