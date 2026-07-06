<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Trait\UuidEntityTrait;
use App\Repository\BudgetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('OWNERSHIP_VIEW', object)"),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('OWNERSHIP_EDIT', object)"),
        new Delete(security: "is_granted('OWNERSHIP_EDIT', object)"),
    ],
    normalizationContext: ['groups' => ['budget:read']],
    denormalizationContext: ['groups' => ['budget:write']],
)]
#[ORM\Entity(repositoryClass: BudgetRepository::class)]
#[ORM\Table(name: 'budgets')]
class Budget implements OwnableInterface
{
    use UuidEntityTrait;

    #[Groups(['budget:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[Groups(['budget:read', 'budget:write'])]
    #[Assert\NotNull(message: 'La catégorie est obligatoire.')]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[Groups(['budget:read', 'budget:write'])]
    #[Assert\NotNull(message: 'Le mois du budget est obligatoire.')]
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $periodStart = null;

    #[Groups(['budget:read', 'budget:write'])]
    #[Assert\Positive(message: 'Le plafond doit être positif.')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $limitAmount = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPeriodStart(): ?\DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function setPeriodStart(\DateTimeImmutable $periodStart): static
    {
        $this->periodStart = $periodStart;

        return $this;
    }

    public function getLimitAmount(): ?string
    {
        return $this->limitAmount;
    }

    public function setLimitAmount(string $limitAmount): static
    {
        $this->limitAmount = $limitAmount;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
