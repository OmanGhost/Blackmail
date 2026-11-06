<?php

namespace App\Support;

use InvalidArgumentException;

/** Amounts are stored as integers in minor units (OMR has 3 decimals: 1 OMR = 1000 baisa). */
final class Money
{
    public const DECIMALS = 3;

    /** "12.5" or 12.5 → 12500. Rejects negatives and more than 3 decimals. */
    public static function toMinor(string|int|float|null $amount): int
    {
        $value = trim((string) $amount);
        if ($value === '') {
            return 0;
        }
        if (! preg_match('/^\d{1,9}(?:\.(\d{1,3}))?$/D', $value, $match)) {
            throw new InvalidArgumentException('Invalid amount: '.$value);
        }
        [$whole] = explode('.', $value);

        return (int) $whole * 1000 + (int) str_pad($match[1] ?? '', self::DECIMALS, '0');
    }

    /** 12500 → "12.500" */
    public static function decimal(int $minor): string
    {
        return intdiv($minor, 1000).'.'.str_pad((string) ($minor % 1000), self::DECIMALS, '0', STR_PAD_LEFT);
    }

    /** 12500 → "12.500 ر.ع." / "OMR 12.500" */
    public static function format(int $minor, string $currency = 'OMR'): string
    {
        $amount = number_format(intdiv($minor, 1000)).'.'.str_pad((string) ($minor % 1000), self::DECIMALS, '0', STR_PAD_LEFT);

        return app()->getLocale() === 'en' ? $currency.' '.$amount : $amount.' '.__('billing.currency.'.$currency);
    }
}
