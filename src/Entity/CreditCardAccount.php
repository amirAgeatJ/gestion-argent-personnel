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
use Symfony\Component\Validator\Constraints as Assert;

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
class CreditCardAccount extends Account
{
    #[Groups(['account:read', 'account:write'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $creditLimit = null;

    #[Groups(['account:read', 'account:write'])]
    #[Assert\Range(min: 1, max: 28)]
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $statementDay = null;

    public function getCreditLimit(): ?string
    {
        return $this->creditLimit;
    }

    public function setCreditLimit(?string $creditLimit): static
    {
        $this->creditLimit = $creditLimit;

        return $this;
    }

    public function getStatementDay(): ?int
    {
        return $this->statementDay;
    }

    public function setStatementDay(?int $statementDay): static
    {
        $this->statementDay = $statementDay;

        return $this;
    }

    public function getTypeLabel(): string
    {
        return 'Carte de crédit';
    }
}
