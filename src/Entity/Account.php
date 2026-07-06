<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Trait\UuidEntityTrait;
use App\Repository\AccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')", securityMessage: 'Connectez-vous pour consulter vos comptes.'),
        new Get(security: "is_granted('ACCOUNT_VIEW', object)"),
    ],
    normalizationContext: ['groups' => ['account:read']],
)]
#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 30)]
#[ORM\DiscriminatorMap([
    'checking' => CheckingAccount::class,
    'savings' => SavingsAccount::class,
    'credit_card' => CreditCardAccount::class,
])]
abstract class Account
{
    use UuidEntityTrait;

    #[Groups(['account:read'])]
    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected ?User $owner = null;

    #[Groups(['account:read', 'account:write'])]
    #[Assert\NotBlank(message: 'Le nom du compte est obligatoire.')]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    protected ?string $name = null;

    #[Groups(['account:read', 'account:write'])]
    #[Assert\Choice(choices: ['EUR', 'USD', 'GBP', 'CHF'], message: 'La devise sélectionnée n\'est pas valide.')]
    #[ORM\Column(length: 3)]
    protected string $currency = 'EUR';

    #[Groups(['account:read'])]
    #[ORM\Column]
    protected bool $isActive = true;

    #[Groups(['account:read'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** Discriminant lisible pour l'UI, ex. "Compte courant". */
    abstract public function getTypeLabel(): string;
}
