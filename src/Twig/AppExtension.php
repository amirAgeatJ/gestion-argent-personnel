<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\DisplayCurrencyProvider;
use App\Service\ExchangeRateProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly DisplayCurrencyProvider $displayCurrencyProvider,
        private readonly ExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('money', $this->formatMoney(...)),
            new TwigFilter('signed_money', $this->formatSignedMoney(...)),
            new TwigFilter('display_money', $this->formatInDisplayCurrency(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('display_currency', $this->displayCurrencyProvider->getCurrency(...)),
        ];
    }

    public function formatMoney(string|float|null $amount, string $currency = 'EUR'): string
    {
        $formatter = new \NumberFormatter('fr_FR', \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency((float) ($amount ?? 0), $currency);
    }

    public function formatSignedMoney(string|float|null $amount, string $type, string $currency = 'EUR'): string
    {
        $value = (float) ($amount ?? 0);
        $sign = match ($type) {
            'income' => '+',
            'expense' => '-',
            default => '',
        };

        return $sign.' '.$this->formatMoney(abs($value), $currency);
    }

    public function formatInDisplayCurrency(string|float|null $amount, string $fromCurrency): string
    {
        $displayCurrency = $this->displayCurrencyProvider->getCurrency();
        $converted = $this->exchangeRateProvider->convert((string) ($amount ?? '0'), $fromCurrency, $displayCurrency);

        return $this->formatMoney($converted, $displayCurrency);
    }
}
