<?php

namespace Saleh7\Zatca\Mappers;

use InvalidArgumentException;

/**
 * @internal
 */
final class TaxPercent
{
    /**
     * Reads a mandatory VAT rate; there is no default, a wrong rate makes the invoice invalid.
     *
     * @param  array<array-key, mixed>  $data
     *
     * @throws InvalidArgumentException when the percent is missing or not numeric.
     */
    public static function required(array $data, string $context): float
    {
        $percent = $data['percent'] ?? null;

        if (! is_numeric($percent)) {
            throw new InvalidArgumentException(sprintf('The "percent" of the %s is required and must be numeric.', $context));
        }

        return (float) $percent;
    }
}
