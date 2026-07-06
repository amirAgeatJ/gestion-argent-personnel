<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\UuidEntityTrait;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use UuidEntityTrait;

    #[Assert\NotBlank(message: 'L\'adresse email est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide.')]
    #[Assert\Length(max: 255, maxMessage: 'L\'adresse email ne peut pas dépasser {{ limit }} caractères.')]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.', groups: ['create'])]
    #[ORM\Column(length: 255)]
    private ?string $passwordHash = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[Assert\Choice(choices: ['active', 'suspended'], message: 'Le statut sélectionné n\'est pas valide.')]
    #[ORM\Column(length: 20)]
    private string $status = 'active';

    #[Assert\Choice(choices: ['EUR', 'USD', 'GBP', 'CHF'], message: 'La devise sélectionnée n\'est pas valide.')]
    #[ORM\Column(length: 3)]
    private string $preferredCurrency = 'EUR';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /** @return list<string> */
    public function getAssignedRoles(): array
    {
        return $this->roles;
    }

    /** @param list<string> $roles */
    public function setAssignedRoles(array $roles): static
    {
        $this->roles = array_values(array_unique(array_filter(
            $roles,
            static fn (string $role): bool => $role !== 'ROLE_USER',
        )));

        return $this;
    }

    public function hasAssignedRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function addAssignedRole(string $role): static
    {
        if ($role !== 'ROLE_USER' && !in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeAssignedRole(string $role): static
    {
        $this->roles = array_values(array_filter(
            $this->roles,
            static fn (string $assignedRole): bool => $assignedRole !== $role,
        ));

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    #[Ignore]
    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName, $this->lastName));
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPreferredCurrency(): string
    {
        return $this->preferredCurrency;
    }

    public function setPreferredCurrency(string $preferredCurrency): static
    {
        $this->preferredCurrency = $preferredCurrency;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
