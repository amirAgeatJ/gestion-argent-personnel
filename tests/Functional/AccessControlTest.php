<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessControlTest extends WebTestCase
{
    public function testAnonymousUserIsRedirectedToLoginFromDashboard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    public function testLoginPageIsAccessibleToEveryone(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }

    public function testRegularUserCannotAccessAdmin(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser(sprintf('user-%s@example.com', bin2hex(random_bytes(8))), []));

        $client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessAdminHome(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser(sprintf('admin-%s@example.com', bin2hex(random_bytes(8))), ['ROLE_ADMIN']));

        $client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
    }

    /** @param list<string> $roles */
    private function createUser(string $email, array $roles): User
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setStatus('active');
        $user->setPreferredCurrency('EUR');
        $user->setAssignedRoles($roles);
        $user->setPasswordHash($passwordHasher->hashPassword($user, 'password'));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
