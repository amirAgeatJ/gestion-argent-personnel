<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\CheckingAccount;
use App\Entity\User;
use App\Repository\AdvisorAssignmentRepository;
use App\Security\Voter\AccountVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class AccountVoterTest extends TestCase
{
    public function testOwnerCanViewEditAndDeleteTheirOwnAccount(): void
    {
        $owner = $this->createUser('owner@example.com');
        $account = $this->createAccount($owner);

        $voter = new AccountVoter($this->createMock(AdvisorAssignmentRepository::class));
        $token = new UsernamePasswordToken($owner, 'main', $owner->getRoles());

        foreach ([AccountVoter::VIEW, AccountVoter::EDIT, AccountVoter::DELETE] as $attribute) {
            self::assertSame(
                VoterInterface::ACCESS_GRANTED,
                $voter->vote($token, $account, [$attribute]),
                sprintf('Owner should be granted "%s".', $attribute),
            );
        }
    }

    public function testUnrelatedUserIsDeniedEvenForView(): void
    {
        $owner = $this->createUser('owner@example.com');
        $stranger = $this->createUser('stranger@example.com');
        $account = $this->createAccount($owner);

        $advisorAssignmentRepository = $this->createMock(AdvisorAssignmentRepository::class);
        $advisorAssignmentRepository->method('isAdvisorOf')->willReturn(false);

        $voter = new AccountVoter($advisorAssignmentRepository);
        $token = new UsernamePasswordToken($stranger, 'main', $stranger->getRoles());

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $account, [AccountVoter::VIEW]));
    }

    public function testAdvisorCanOnlyViewNotEditAnAssignedClientsAccount(): void
    {
        $owner = $this->createUser('client@example.com');
        $advisor = $this->createUser('advisor@example.com');
        $advisor->setAssignedRoles(['ROLE_ADVISOR']);
        $account = $this->createAccount($owner);

        $advisorAssignmentRepository = $this->createMock(AdvisorAssignmentRepository::class);
        $advisorAssignmentRepository->method('isAdvisorOf')->willReturn(true);

        $voter = new AccountVoter($advisorAssignmentRepository);
        $token = new UsernamePasswordToken($advisor, 'main', $advisor->getRoles());

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $account, [AccountVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $account, [AccountVoter::EDIT]));
    }

    public function testAdminIsAlwaysGranted(): void
    {
        $owner = $this->createUser('client@example.com');
        $admin = $this->createUser('admin@example.com');
        $admin->setAssignedRoles(['ROLE_ADMIN']);
        $account = $this->createAccount($owner);

        $voter = new AccountVoter($this->createMock(AdvisorAssignmentRepository::class));
        $token = new UsernamePasswordToken($admin, 'main', $admin->getRoles());

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $account, [AccountVoter::DELETE]));
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPasswordHash('irrelevant');

        return $user;
    }

    private function createAccount(User $owner): CheckingAccount
    {
        $account = new CheckingAccount();
        $account->setOwner($owner);
        $account->setName('Compte courant');
        $account->setCurrency('EUR');

        return $account;
    }
}
