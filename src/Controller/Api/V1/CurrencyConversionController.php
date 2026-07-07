<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\ExchangeRateProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;


final class CurrencyConversionController
{
    public function __construct(
        private readonly ExchangeRateProvider $exchangeRateProvider,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/v1/convert', name: 'api_v1_currency_convert', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $from = strtoupper($request->query->getString('from'));
        $to = strtoupper($request->query->getString('to'));
        $amount = $request->query->getString('amount', '0');

        $violations = $this->validator->validate($from, [new Assert\Currency()]);
        $violations->addAll($this->validator->validate($to, [new Assert\Currency()]));

        if (count($violations) > 0 || !is_numeric($amount) || (float) $amount <= 0) {
            return new JsonResponse(['error' => 'Paramètres invalides : "from"/"to" doivent être des codes devise ISO 4217, "amount" un nombre positif.'], 400);
        }

        $result = new CurrencyConversionResult(
            from: $from,
            to: $to,
            amount: $amount,
            rate: $this->exchangeRateProvider->getRate($from, $to),
            convertedAmount: $this->exchangeRateProvider->convert($amount, $from, $to),
        );

        $json = $this->serializer->serialize($result, 'json', ['groups' => ['conversion:read']]);

        return JsonResponse::fromJsonString($json);
    }
}
