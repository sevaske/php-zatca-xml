<?php

namespace Saleh7\Zatca;

use InvalidArgumentException;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Class TaxTotal
 *
 * Represents the total tax details for an invoice and provides XML serialization.
 */
class TaxTotal implements XmlSerializable
{
    /** @var float|null Total tax amount. */
    public ?float $taxAmount = null;

    /** @var float|null Rounding amount. */
    private ?float $roundingAmount = null;

    /**
     * @var TaxSubTotal[] Array of tax subtotals.
     */
    private array $taxSubTotals = [];

    /**
     * Set the total tax amount.
     *
     * @throws InvalidArgumentException if tax amount is negative.
     */
    public function setTaxAmount(?float $taxAmount): self
    {
        if ($taxAmount !== null && $taxAmount < 0) {
            throw new InvalidArgumentException('Tax amount must be non-negative.');
        }
        $this->taxAmount = $taxAmount;

        return $this;
    }

    /**
     * Set the rounding amount.
     *
     * @throws InvalidArgumentException if rounding amount is negative.
     */
    public function setRoundingAmount(?float $roundingAmount): self
    {
        if ($roundingAmount !== null && $roundingAmount < 0) {
            throw new InvalidArgumentException('Rounding amount must be non-negative.');
        }
        $this->roundingAmount = $roundingAmount;

        return $this;
    }

    /**
     * Adds a TaxSubTotal object to the tax subtotals array.
     */
    public function addTaxSubTotal(TaxSubTotal $taxSubTotal): self
    {
        $this->taxSubTotals[] = $taxSubTotal;

        return $this;
    }

    /**
     * Validates that required fields are set.
     *
     * @throws InvalidArgumentException if taxAmount is not set.
     */
    public function validate(): void
    {
        if ($this->taxAmount === null) {
            throw new InvalidArgumentException('Missing TaxTotal taxAmount.');
        }
    }

    /**
     * Serializes this object to XML.
     *
     * @param  Writer  $writer  The XML writer instance.
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $currencyID = CurrencyWriter::currencyOf($writer);

        // Write TaxAmount element
        $writer->write([
            [
                'name' => Schema::CBC.'TaxAmount',
                'value' => number_format($this->taxAmount, 2, '.', ''),
                'attributes' => [
                    'currencyID' => $currencyID,
                ],
            ],
        ]);

        // Write RoundingAmount element if set
        if ($this->roundingAmount !== null) {
            $writer->write([
                [
                    'name' => Schema::CBC.'RoundingAmount',
                    'value' => number_format($this->roundingAmount, 2, '.', ''),
                    'attributes' => [
                        'currencyID' => $currencyID,
                    ],
                ],
            ]);
        }

        // Write each TaxSubTotal element
        foreach ($this->taxSubTotals as $taxSubTotal) {
            $writer->write([
                Schema::CAC.'TaxSubtotal' => $taxSubTotal,
            ]);
        }
    }
}
