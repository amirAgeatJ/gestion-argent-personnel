<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\OwnableInterface;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter générique pour toute entité "possédée" par un utilisateur
 * (Budget, SavingsGoal, RecurringTransaction) : seul le propriétaire ou un admin y accède.
 */
final class OwnershipVoter extends Voter
{
    public const string VIEW = 'OWNERSHIP_VIEW';
    public const string EDIT = 'OWNERSHIP_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true) && $subject instanceof OwnableInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$subject instanceof OwnableInterface) {
            return false;
        }

        return $subject->getOwner() === $user || in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
