<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\UuidEntityTrait;
use App\Repository\ExchangeRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRateRepository::class)]
#[ORM\Table(name: 'exchange_rates')]
#[ORM\UniqueConstraint(name: 'uniq_currency_pair', columns: ['base_currency', 'target_currency'])]
class ExchangeRate
{
    use UuidEntityTrait;

    #[ORM\Column(length: 3)]
    private ?string $baseCurrency = null;

    #[ORM\Column(length: 3)]
    private ?string $targetCurrency = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 8)]
    private ?string $rate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $fetchedAt = null;

    public function getBaseCurrency(): ?string
    {
        return $this->baseCurrency;
    }

    public function setBaseCurrency(string $baseCurrency): static
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    public function getTargetCurrency(): ?string
    {
        return $this->targetCurrency;
    }

    public function setTargetCurrency(string $targetCurrency): static
    {
        $this->targetCurrency = $targetCurrency;

        return $this;
    }

    public function getRate(): ?string
    {
        return $this->rate;
    }

    public function setRate(string $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getFetchedAt(): ?\DateTimeImmutable
    {
        return $this->fetchedAt;
    }

    public function setFetchedAt(\DateTimeImmutable $fetchedAt): static
    {
        $this->fetchedAt = $fetchedAt;

        return $this;
    }

    public function isFreshAsOf(\DateTimeImmutable $now, int $maxAgeHours = 24): bool
    {
        return $this->fetchedAt !== null
            && $this->fetchedAt->modify(sprintf('+%d hours', $maxAgeHours)) > $now;
    }
}
