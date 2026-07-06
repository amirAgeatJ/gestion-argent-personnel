<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Devise d'affichage choisie par l'utilisateur via le sélecteur de la topbar (stockée en
 * session, ne modifie jamais les montants stockés ni la devise réelle des comptes).
 */
class DisplayCurrencyProvider
{
    public const array SUPPORTED = ['EUR', 'USD', 'GBP', 'CHF'];

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getCurrency(): string
    {
        return $this->requestStack->getSession()->get('display_currency', 'EUR');
    }

    public function setCurrency(string $currency): void
    {
        if (in_array($currency, self::SUPPORTED, true)) {
            $this->requestStack->getSession()->set('display_currency', $currency);
        }
    }
}
