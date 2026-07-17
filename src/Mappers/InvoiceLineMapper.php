<?php

namespace Saleh7\Zatca\Mappers;

use Saleh7\Zatca\AllowanceCharge;
use Saleh7\Zatca\InvoiceLine;
use Saleh7\Zatca\TaxCategory;
use Saleh7\Zatca\TaxScheme;
use Saleh7\Zatca\TaxTotal;

/**
 * Class InvoiceLineMapper
 *
 * Maps invoice line data (from an array) to an array of InvoiceLine objects.
 */
class InvoiceLineMapper
{
    /**
     * @var ItemMapper Mapper for converting item data.
     */
    private ItemMapper $itemMapper;

    /**
     * @var PriceMapper Mapper for converting price data.
     */
    private PriceMapper $priceMapper;

    /**
     * InvoiceLineMapper constructor.
     *
     * Initializes the dependent mappers.
     */
    public function __construct()
    {
        $this->itemMapper = new ItemMapper;
        $this->priceMapper = new PriceMapper;
    }

    /**
     * Map an array of invoice line data to an array of InvoiceLine objects.
     *
     * Expected input for each line:
     * [
     *   'id' => (string|int),
     *   'unitCode' => (string),
     *   'lineExtensionAmount' => (float),
     *   'quantity' => (int|float),
     *   'item' => [ ... ],     // Data for item mapping.
     *   'price' => [ ... ],    // Data for price mapping.
     *   'taxTotal' => [        // Data for tax total mapping.
     *       'taxAmount' => (float),
     *       'roundingAmount' => (float)
     *   ]
     * ]
     *
     * @param  array  $lines  Array of invoice lines data.
     * @return InvoiceLine[] Array of mapped InvoiceLine objects.
     */
    public function mapInvoiceLines(array $lines): array
    {
        $invoiceLines = [];
        foreach ($lines as $line) {
            // Map item data using ItemMapper.
            $item = $this->itemMapper->map($line['item'] ?? []);
            // Map price data using PriceMapper.
            $price = $this->priceMapper->map($line['price'] ?? []);
            // Map line tax total data.
            $taxTotal = $this->mapLineTaxTotal($line['taxTotal'] ?? []);
            // Create and populate the InvoiceLine object.
            $invoiceLine = (new InvoiceLine)
                ->setUnitCode($line['unitCode'] ?? 'PCE')
                ->setId((string) ($line['id'] ?? '1'))
                ->setItem($item)
                ->setLineExtensionAmount($line['lineExtensionAmount'] ?? 0)
                ->setAllowanceCharges($this->mapAllowanceCharge($line ?? []))
                ->setPrice($price)
                ->setTaxTotal($taxTotal)
                ->setInvoicedQuantity($line['quantity'] ?? 0);
            $invoiceLines[] = $invoiceLine;
        }

        return $invoiceLines;
    }

    /**
     * Map line tax total data to a TaxTotal object.
     *
     * Expected input:
     * [
     *   'taxAmount' => (float),
     *   'roundingAmount' => (float)
     * ]
     *
     * @param  array  $data  Array of line tax total data.
     * @return TaxTotal The mapped TaxTotal object.
     */
    private function mapLineTaxTotal(array $data): TaxTotal
    {
        return (new TaxTotal)
            ->setTaxAmount($data['taxAmount'] ?? 0)
            ->setRoundingAmount($data['roundingAmount'] ?? 0);
    }

    /**
     * Map AllowanceCharge data to an array of AllowanceCharge objects.
     *
     * @param  array  $data  The invoice data containing allowance charges.
     * @return AllowanceCharge[] Array of mapped AllowanceCharge objects.
     */
    private function mapAllowanceCharge(array $data): array
    {
        $allowanceCharges = [];
        // Check if allowanceCharges is an array.
        if (! isset($data['allowanceCharges']) || ! is_array($data['allowanceCharges'])) {
            return $allowanceCharges;
        }
        // Iterate over each allowance charge in the data.
        foreach ($data['allowanceCharges'] as $allowanceCharge) {
            $taxCategories = [];

            // Check if taxCategories is an array and iterate over it.
            if (isset($allowanceCharge['taxCategories']) && is_array($allowanceCharge['taxCategories'])) {
                foreach ($allowanceCharge['taxCategories'] as $taxCatData) {
                    $taxCategory = (new TaxCategory)
                        ->setPercent($taxCatData['percent'])
                        ->setTaxScheme(
                            (new TaxScheme)
                                ->setId($taxCatData['taxScheme']['id'] ?? 'VAT')
                        );

                    // Allow explicit ZATCA VAT category code (S, Z, E, O)
                    if (isset($taxCatData['id'])) {
                        $taxCategory->setId($taxCatData['id']);
                    }

                    $taxCategories[] = $taxCategory;
                }
            }

            // Create the AllowanceCharge object with its tax categories.
            $allowanceCharges[] = (new AllowanceCharge)
                ->setChargeIndicator($allowanceCharge['isCharge'] ?? false)
                ->setAllowanceChargeReason($allowanceCharge['reason'] ?? 'discount')
                ->setAmount($allowanceCharge['amount'] ?? 0.00)
                ->setTaxCategory($taxCategories);
        }

        return $allowanceCharges;
    }
}
