<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Transaction;

class TransferService
{
    public function __construct(
        private readonly ExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    public function prepareTransfer(Transaction $transaction): void
    {
        if ($transaction->getType() !== 'transfer') {
            $transaction->setConvertedAmount(null);

            return;
        }

        $destinationAccount = $transaction->getTransferToAccount();

        if ($destinationAccount === null || $destinationAccount->getCurrency() === $transaction->getCurrency()) {
            $transaction->setConvertedAmount(null);

            return;
        }

        $converted = $this->exchangeRateProvider->convert(
            $transaction->getAmount(),
            $transaction->getCurrency(),
            $destinationAccount->getCurrency(),
        );

        $transaction->setConvertedAmount($converted);
    }
}
