<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExchangeRate;
use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateProvider
{
    private const string API_BASE_URL = 'https://api.frankfurter.app';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getRate(string $base, string $target): string
    {
        if ($base === $target) {
            return '1.00000000';
        }

        $now = new \DateTimeImmutable();
        $cached = $this->exchangeRateRepository->findFreshOrNull($base, $target, $now);

        if ($cached !== null) {
            return $cached->getRate();
        }

        return $this->refresh($base, $target, $now);
    }

    public function convert(string $amount, string $base, string $target): string
    {
        $rate = $this->getRate($base, $target);

        return bcmul($amount, $rate, 2);
    }

    private function refresh(string $base, string $target, \DateTimeImmutable $now): string
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL.'/latest', [
                'query' => ['from' => $base, 'to' => $target],
            ]);
            $data = $response->toArray();
            $rate = (string) ($data['rates'][$target] ?? null);

            if ($rate === '' || $rate === '0') {
                throw new \RuntimeException(sprintf('Taux de change introuvable pour %s -> %s.', $base, $target));
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('Échec de récupération du taux de change Frankfurter, utilisation de la dernière valeur connue.', [
                'base' => $base,
                'target' => $target,
                'exception' => $exception->getMessage(),
            ]);

            $stale = $this->exchangeRateRepository->findOneBy(['baseCurrency' => $base, 'targetCurrency' => $target]);

            return $stale?->getRate() ?? '1.00000000';
        }

        $entity = $this->exchangeRateRepository->findOneBy(['baseCurrency' => $base, 'targetCurrency' => $target])
            ?? new ExchangeRate();

        $entity->setBaseCurrency($base);
        $entity->setTargetCurrency($target);
        $entity->setRate($rate);
        $entity->setFetchedAt($now);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $rate;
    }
}
