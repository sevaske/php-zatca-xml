<?php

namespace Saleh7\Zatca\Mappers\Validators;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use InvalidArgumentException;

/**
 * Class InvoiceAmountValidator
 *
 * Validates financial fields in invoice data, including monetary totals and line-level
 * consistency. Ensures that amounts are numeric, non-negative, and internally coherent.
 */
class InvoiceAmountValidator
{
    /**
     * Default tolerance for money comparison: 0.01.
     */
    private BigDecimal $tolerance;

    public function __construct(BigNumber|int|float|string $tolerance = 0.01)
    {
        $this->tolerance = BigDecimal::of((string) $tolerance);
    }

    /**
     * Validate the monetary totals in the invoice.
     *
     * @param  array  $data  The invoice data array.
     *
     * @throws InvalidArgumentException
     */
    public function validateMonetaryTotals(array $data): void
    {
        if (! isset($data['legalMonetaryTotal'])) {
            throw new InvalidArgumentException('Legal Monetary Total section is missing.');
        }

        $lmt = $data['legalMonetaryTotal'];
        $requiredFields = [
            'lineExtensionAmount',
            'taxExclusiveAmount',
            'taxInclusiveAmount',
            'payableAmount',
        ];

        foreach ($requiredFields as $field) {
            if (! isset($lmt[$field]) || ! is_numeric($lmt[$field])) {
                throw new InvalidArgumentException("Legal Monetary Total field '{$field}' must be a numeric value.");
            }

            if (BigDecimal::of((string) $lmt[$field])->isNegative()) {
                throw new InvalidArgumentException("Legal Monetary Total field '{$field}' cannot be negative.");
            }
        }

        $taxTotalAmount = isset($data['taxTotal']['taxAmount']) && is_numeric($data['taxTotal']['taxAmount'])
            ? BigDecimal::of((string) $data['taxTotal']['taxAmount'])
            : BigDecimal::zero();

        $taxExclusiveAmount = BigDecimal::of((string) $lmt['taxExclusiveAmount']);
        $expectedTaxInclusive = $taxExclusiveAmount->plus($taxTotalAmount);
        $actualTaxInclusive = BigDecimal::of((string) $lmt['taxInclusiveAmount']);

        $this->assertMoneyEquals(
            $expectedTaxInclusive,
            $actualTaxInclusive,
            "The taxInclusiveAmount ({$actualTaxInclusive}) does not equal taxExclusiveAmount ({$taxExclusiveAmount}) plus taxTotal ({$taxTotalAmount})."
        );
    }

    /**
     * Validate invoice lines for numeric consistency and calculation correctness.
     *
     * @param  array  $invoiceLines  Array of invoice lines.
     *
     * @throws InvalidArgumentException
     */
    public function validateInvoiceLines(array $invoiceLines): void
    {
        foreach ($invoiceLines as $index => $line) {
            $this->validateNumericField($line, 'quantity', $index);
            $this->validateNumericField($line, 'lineExtensionAmount', $index);

            if (! isset($line['price']['amount']) || ! is_numeric($line['price']['amount'])) {
                throw new InvalidArgumentException("Invoice Line [{$index}] Price amount must be a numeric value.");
            }

            $priceAmount = BigDecimal::of((string) $line['price']['amount']);
            if ($priceAmount->isLessThan(BigDecimal::zero())) {
                throw new InvalidArgumentException("Invoice Line [{$index}] Price amount cannot be negative.");
            }

            // Expected = price * quantity
            $expectedLineExtension = $priceAmount->multipliedBy($line['quantity']);
            $providedLineExtension = BigDecimal::of((string) $line['lineExtensionAmount']);

            $this->assertMoneyEquals(
                $expectedLineExtension,
                $providedLineExtension,
                "Invoice Line [{$index}] lineExtensionAmount is incorrect. Expected {$expectedLineExtension}, got {$providedLineExtension}."
            );

            // Validate taxPercent
            if (isset($line['item']['taxPercent'])) {
                if (! is_numeric($line['item']['taxPercent'])) {
                    throw new InvalidArgumentException("Invoice Line [{$index}] item taxPercent must be a numeric value.");
                }

                $taxPercent = (float) $line['item']['taxPercent'];
                if ($taxPercent < 0 || $taxPercent > 100) {
                    throw new InvalidArgumentException("Invoice Line [{$index}] item taxPercent must be between 0 and 100.");
                }
            }

            // Validate taxTotal.taxAmount
            if (! isset($line['taxTotal']['taxAmount']) || ! is_numeric($line['taxTotal']['taxAmount'])) {
                throw new InvalidArgumentException("Invoice Line [{$index}] TaxTotal taxAmount must be a numeric value.");
            }

            $taxLineAmount = BigDecimal::of((string) $line['taxTotal']['taxAmount']);
            if ($taxLineAmount->isLessThan(BigDecimal::zero())) {
                throw new InvalidArgumentException("Invoice Line [{$index}] TaxTotal taxAmount cannot be negative.");
            }

            // Validate roundingAmount = lineExtensionAmount + taxAmount
            if (! isset($line['taxTotal']['roundingAmount']) || ! is_numeric($line['taxTotal']['roundingAmount'])) {
                throw new InvalidArgumentException("Invoice Line [{$index}] TaxTotal roundingAmount must be a numeric value.");
            }

            $roundingAmount = BigDecimal::of((string) $line['taxTotal']['roundingAmount']);
            $expectedRounding = $providedLineExtension->plus($taxLineAmount);

            $this->assertMoneyEquals(
                $expectedRounding,
                $roundingAmount,
                "Invoice Line [{$index}] roundingAmount is incorrect. Expected {$expectedRounding}, got {$roundingAmount}."
            );
        }
    }

    /**
     * Helper to validate that a numeric field exists and is non-negative.
     */
    private function validateNumericField(array $line, string $field, int $index): void
    {
        if (! isset($line[$field]) || ! is_numeric($line[$field])) {
            throw new InvalidArgumentException("Invoice Line [{$index}] field '{$field}' must be a numeric value.");
        }

        if (BigDecimal::of((string) $line[$field])->isLessThan(BigDecimal::zero())) {
            throw new InvalidArgumentException("Invoice Line [{$index}] field '{$field}' cannot be negative.");
        }
    }

    /**
     * Helper to compare two monetary BigDecimal values within tolerance.
     */
    private function assertMoneyEquals(BigDecimal $a, BigDecimal $b, string $message): void
    {
        if ($a->minus($b)->abs()->isGreaterThan($this->tolerance)) {
            throw new InvalidArgumentException($message);
        }
    }
}
