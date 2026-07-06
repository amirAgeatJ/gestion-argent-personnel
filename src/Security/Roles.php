<?php

declare(strict_types=1);

namespace App\Security;

final class Roles
{
    public const string ADVISOR = 'ROLE_ADVISOR';
    public const string ADMIN = 'ROLE_ADMIN';

    public const array ASSIGNABLE = [
        self::ADVISOR => 'Conseiller',
        self::ADMIN => 'Administrateur',
    ];

    public static function label(string $role): string
    {
        return self::ASSIGNABLE[$role] ?? $role;
    }

    /** @param list<string> $roles */
    public static function primaryLabel(array $roles): string
    {
        foreach ([self::ADMIN, self::ADVISOR] as $role) {
            if (in_array($role, $roles, true)) {
                return self::label($role);
            }
        }

        return 'Utilisateur';
    }
}
