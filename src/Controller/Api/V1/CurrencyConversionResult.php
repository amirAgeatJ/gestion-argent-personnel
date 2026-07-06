<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class CurrencyConversionResult
{
    public function __construct(
        #[Groups(['conversion:read'])]
        public string $from,
        #[Groups(['conversion:read'])]
        public string $to,
        #[Groups(['conversion:read'])]
        public string $amount,
        #[Groups(['conversion:read'])]
        public string $rate,
        #[Groups(['conversion:read'])]
        public string $convertedAmount,
    ) {
    }
}
