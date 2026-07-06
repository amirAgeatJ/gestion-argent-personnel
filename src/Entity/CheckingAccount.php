<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ACCOUNT_EDIT', object)"),
        new Delete(security: "is_granted('ACCOUNT_DELETE', object)"),
    ],
    normalizationContext: ['groups' => ['account:read']],
    denormalizationContext: ['groups' => ['account:write']],
)]
#[ORM\Entity]
class CheckingAccount extends Account
{
    #[Groups(['account:read', 'account:write'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $overdraftLimit = null;

    public function getOverdraftLimit(): ?string
    {
        return $this->overdraftLimit;
    }

    public function setOverdraftLimit(?string $overdraftLimit): static
    {
        $this->overdraftLimit = $overdraftLimit;

        return $this;
    }

    public function getTypeLabel(): string
    {
        return 'Compte courant';
    }
}
