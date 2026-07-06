<?php

declare(strict_types=1);

namespace App\Faker;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fournisseur Faker personnalisé pour Alice/hautelook-fixtures : permet d'écrire
 * `passwordHash: '<hashedPassword("password")>'` dans les fichiers YAML et d'obtenir
 * un vrai hash (et non une chaîne aléatoire), pour que les comptes de test puissent se connecter.
 */
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
