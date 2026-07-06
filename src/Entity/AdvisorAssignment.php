<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\UuidEntityTrait;
use App\Repository\AdvisorAssignmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdvisorAssignmentRepository::class)]
#[ORM\Table(name: 'advisor_assignments')]
#[ORM\UniqueConstraint(name: 'uniq_advisor_client', columns: ['advisor_id', 'client_id'])]
#[Assert\Expression(
    'this.getAdvisor() === null or this.getClient() === null or this.getAdvisor() !== this.getClient()',
    message: 'Un conseiller ne peut pas être son propre client.',
)]
class AdvisorAssignment
{
    use UuidEntityTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $advisor = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $client = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $assignedAt = null;

    public function __construct()
    {
        $this->assignedAt = new \DateTimeImmutable();
    }

    public function getAdvisor(): ?User
    {
        return $this->advisor;
    }

    public function setAdvisor(User $advisor): static
    {
        $this->advisor = $advisor;

        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(User $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getAssignedAt(): ?\DateTimeImmutable
    {
        return $this->assignedAt;
    }
}
