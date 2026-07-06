<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\UuidEntityTrait;
use App\Repository\RecurringTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecurringTransactionRepository::class)]
#[ORM\Table(name: 'recurring_transactions')]
class RecurringTransaction implements OwnableInterface
{
    use UuidEntityTrait;

    #[Assert\NotNull(message: 'Le compte est obligatoire.')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $category = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[Assert\Choice(choices: ['income', 'expense'], message: 'Le type sélectionné n\'est pas valide.')]
    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[Assert\Positive(message: 'Le montant doit être positif.')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[Assert\Choice(choices: ['weekly', 'monthly', 'yearly'], message: 'La fréquence sélectionnée n\'est pas valide.')]
    #[ORM\Column(length: 20)]
    private ?string $frequency = null;

    #[Assert\NotNull(message: 'La prochaine date d\'exécution est obligatoire.')]
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $nextRunDate = null;

    #[ORM\Column]
    private bool $active = true;

    public function getOwner(): ?User
    {
        return $this->account?->getOwner();
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getNextRunDate(): ?\DateTimeImmutable
    {
        return $this->nextRunDate;
    }

    public function setNextRunDate(\DateTimeImmutable $nextRunDate): static
    {
        $this->nextRunDate = $nextRunDate;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function computeNextRunDate(): \DateTimeImmutable
    {
        return match ($this->frequency) {
            'weekly' => $this->nextRunDate->modify('+1 week'),
            'yearly' => $this->nextRunDate->modify('+1 year'),
            default => $this->nextRunDate->modify('+1 month'),
        };
    }
}
