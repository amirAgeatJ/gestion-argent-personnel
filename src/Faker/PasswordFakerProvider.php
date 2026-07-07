<?php

declare(strict_types=1);

namespace App\Faker;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


final class PasswordFakerProvider
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function hashedPassword(string $plainPassword = 'password'): string
    {
        return $this->passwordHasher->hashPassword(new User(), $plainPassword);
    }
}
