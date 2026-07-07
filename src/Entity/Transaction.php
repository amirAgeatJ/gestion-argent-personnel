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
use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ACCOUNT_VIEW', object.getAccount())"),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ACCOUNT_EDIT', object.getAccount())"),
        new Delete(security: "is_granted('ACCOUNT_EDIT', object.getAccount())"),
    ],
    normalizationContext: ['groups' => ['transaction:read']],
    denormalizationContext: ['groups' => ['transaction:write']],
)]
#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
#[Assert\Callback('validateConsistency')]
class Transaction
{
    use UuidEntityTrait;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[Assert\NotNull(message: 'Le compte est obligatoire.')]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'account_id', nullable: false)]
    private ?Account $account = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true)]
    private ?Category $category = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'transfer_to_account_id', nullable: true)]
    private ?Account $transferToAccount = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[Assert\Choice(choices: ['income', 'expense', 'transfer'], message: 'Le type sélectionné n\'est pas valide.')]
    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[Assert\Positive(message: 'Le montant doit être positif.')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[Assert\Choice(choices: ['EUR', 'USD', 'GBP', 'CHF'], message: 'La devise sélectionnée n\'est pas valide.')]
    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[Groups(['transaction:read'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $convertedAmount = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[Groups(['transaction:read', 'transaction:write'])]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $occurredAt = null;

    /** @var Collection<int, Tag> */
    #[Groups(['transaction:read', 'transaction:write'])]
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'transactions')]
    #[ORM\JoinTable(name: 'transaction_tag')]
    private Collection $tags;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
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

    public function getTransferToAccount(): ?Account
    {
        return $this->transferToAccount;
    }

    public function setTransferToAccount(?Account $transferToAccount): static
    {
        $this->transferToAccount = $transferToAccount;

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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getConvertedAmount(): ?string
    {
        return $this->convertedAmount;
    }

    public function setConvertedAmount(?string $convertedAmount): static
    {
        $this->convertedAmount = $convertedAmount;

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

    public function getOccurredAt(): ?\DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): static
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    /** @return Collection<int, Tag> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function validateConsistency(ExecutionContextInterface $context): void
    {
        if ($this->type === 'transfer') {
            if ($this->transferToAccount === null) {
                $context->buildViolation('Un virement doit indiquer un compte de destination.')
                    ->atPath('transferToAccount')
                    ->addViolation();
            } elseif ($this->account !== null && $this->transferToAccount === $this->account) {
                $context->buildViolation('Le compte de destination doit être différent du compte source.')
                    ->atPath('transferToAccount')
                    ->addViolation();
            }

            return;
        }

        if ($this->transferToAccount !== null) {
            $context->buildViolation('Le compte de destination n\'est utilisé que pour les virements.')
                ->atPath('transferToAccount')
                ->addViolation();
        }

        if ($this->category === null) {
            $context->buildViolation('La catégorie est obligatoire pour un revenu ou une dépense.')
                ->atPath('category')
                ->addViolation();
        }
    }
}
