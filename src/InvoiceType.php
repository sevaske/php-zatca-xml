<?php

namespace Saleh7\Zatca;

use InvalidArgumentException;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Class InvoiceType
 *
 * Represents the type of the invoice for XML serialization.
 */
class InvoiceType implements XmlSerializable
{
    /** @var string|null Main invoice category ("Invoice" or "Simplified"). */
    private ?string $invoice = null;

    /** @var string|null Invoice sub-type ("Invoice", "Debit", or "Credit"). */
    private ?string $invoiceType = null;

    /** @var bool Indicates whether the invoice is an export invoice. */
    private bool $isExportInvoice = false;

    /** @var bool Indicates whether the invoice is a third-party transaction. */
    private bool $isThirdParty = false;

    /** @var bool Indicates whether the invoice is a nominal transaction. */
    private bool $isNominal = false;

    /** @var bool Indicates whether the invoice is a summary invoice. */
    private bool $isSummary = false;

    /** @var bool Indicates whether the invoice is self-billed. */
    private bool $isSelfBilled = false;

    /**
     * Set the invoice category.
     *
     * @throws InvalidArgumentException if provided value is empty.
     */
    public function setInvoice(?string $invoice): self
    {
        if ($invoice !== null && trim($invoice) === '') {
            throw new InvalidArgumentException('Invoice category cannot be empty.');
        }
        $this->invoice = strtolower($invoice);

        return $this;
    }

    /**
     * Set the invoice sub-type.
     *
     * @throws InvalidArgumentException if provided value is empty.
     */
    public function setInvoiceType(?string $invoiceType): self
    {
        if ($invoiceType !== null && trim($invoiceType) === '') {
            throw new InvalidArgumentException('Invoice type cannot be empty.');
        }
        $this->invoiceType = strtolower($invoiceType);

        return $this;
    }

    /**
     * Set whether the invoice is an export invoice.
     */
    public function setIsExportInvoice(?bool $isExportInvoice): self
    {
        $this->isExportInvoice = $isExportInvoice ?? false;

        return $this;
    }

    public function setIsThirdParty(bool $isThirdParty): self
    {
        $this->isThirdParty = $isThirdParty;

        return $this;
    }

    public function setIsNominal(bool $isNominal): self
    {
        $this->isNominal = $isNominal;

        return $this;
    }

    public function setIsSummary(bool $isSummary): self
    {
        $this->isSummary = $isSummary;

        return $this;
    }

    public function setIsSelfBilled(bool $isSelfBilled): self
    {
        $this->isSelfBilled = $isSelfBilled;

        return $this;
    }

    /**
     * Get the invoice category.
     */
    public function getInvoice(): ?string
    {
        return $this->invoice;
    }

    /**
     * Get the invoice sub-type.
     */
    public function getInvoiceType(): ?string
    {
        return $this->invoiceType;
    }

    /**
     * Get the export invoice flag.
     */
    public function isExportInvoice(): bool
    {
        return $this->isExportInvoice;
    }

    /**
     * Serializes this object to XML.
     *
     * @param  Writer  $writer  The XML writer.
     *
     * @throws InvalidArgumentException if invoiceType or invoice is not set or invalid.
     */
    public function xmlSerialize(Writer $writer): void
    {
        if ($this->invoiceType === null || $this->invoice === null) {
            throw new InvalidArgumentException('Invoice category and type must be set.');
        }

        // Determine the base invoice type code based on invoiceType property.
        $invoiceTypeCode = match ($this->invoiceType) {
            'invoice' => InvoiceTypeCode::INVOICE,
            'debit' => InvoiceTypeCode::DEBIT_NOTE,
            'credit' => InvoiceTypeCode::CREDIT_NOTE,
            'prepayment' => InvoiceTypeCode::PREPAYMENT,
            default => throw new InvalidArgumentException('Invalid invoice type provided.'),
        };

        // Determine the complete invoice type value based on the invoice category.
        $invoiceTypeValue = match ($this->invoice) {
            'standard' => match ($this->invoiceType) {
                'invoice', 'debit', 'credit', 'prepayment' => InvoiceTypeCode::STANDARD_INVOICE,
                default => throw new InvalidArgumentException('Invalid invoice type provided.'),
            },
            'simplified' => match ($this->invoiceType) {
                'invoice', 'debit', 'credit', 'prepayment' => InvoiceTypeCode::SIMPLIFIED_INVOICE,
                default => throw new InvalidArgumentException('Invalid invoice type provided.'),
            },
            default => throw new InvalidArgumentException('Invalid invoice category provided.'),
        };

        // Adjust type value based on additional flags.
        if (strlen($invoiceTypeValue) >= 7) {
            $prefix = substr($invoiceTypeValue, 0, 2);
            $p = $this->isThirdParty ? '1' : '0'; // Third-party transaction
            $n = $this->isNominal ? '1' : '0'; // Nominal transaction
            $e = $this->isExportInvoice ? '1' : '0'; // Export invoice
            $s = $this->isSummary ? '1' : '0'; // Summary invoice
            $b = $this->isSelfBilled ? '1' : '0'; // Self-billed invoice

            // Update the invoice type value. [PNESB]
            $invoiceTypeValue = $prefix.$p.$n.$e.$s.$b;
        }

        // Write the InvoiceTypeCode element with attributes.
        $writer->write([
            [
                'name' => Schema::CBC.'InvoiceTypeCode',
                'value' => $invoiceTypeCode,
                'attributes' => [
                    'name' => $invoiceTypeValue,
                ],
            ],
        ]);
    }
}
